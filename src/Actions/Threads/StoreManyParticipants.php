<?php

namespace RTippin\Messenger\Actions\Threads;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use RTippin\Messenger\Broadcasting\NewThreadBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\ParticipantsAddedEvent;
use RTippin\Messenger\Http\Resources\Broadcast\NewThreadBroadcastResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\ProvidersRepository;
use RTippin\Messenger\Support\Helpers;
use Throwable;

class StoreManyParticipants extends ThreadParticipantAction
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var ProvidersRepository
     */
    private ProvidersRepository $providersRepository;

    /**
     * @var DatabaseManager
     */
    private DatabaseManager $database;

    /**
     * @var BroadcastDriver
     */
    private BroadcastDriver $broadcaster;

    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var FriendDriver
     */
    private FriendDriver $friends;

    /**
     * StoreManyParticipants constructor.
     *
     * @param  Messenger  $messenger
     * @param  BroadcastDriver  $broadcaster
     * @param  Dispatcher  $dispatcher
     * @param  ProvidersRepository  $providersRepository
     * @param  DatabaseManager  $database
     * @param  FriendDriver  $friends
     */
    public function __construct(Messenger $messenger,
                                BroadcastDriver $broadcaster,
                                Dispatcher $dispatcher,
                                ProvidersRepository $providersRepository,
                                DatabaseManager $database,
                                FriendDriver $friends)
    {
        $this->messenger = $messenger;
        $this->providersRepository = $providersRepository;
        $this->database = $database;
        $this->broadcaster = $broadcaster;
        $this->dispatcher = $dispatcher;
        $this->friends = $friends;
    }

    /**
     * Store a collection of participants to a group, or restore
     * soft_deleted participants if already existed in trash.
     *
     * @param  Thread  $thread
     * @param  array  $providers
     * @param  bool  $isNewGroup
     * @return $this
     *
     * @throws Throwable
     */
    public function execute(Thread $thread,
                            array $providers,
                            bool $isNewGroup = false): self
    {
        $this->setThread($thread);

        $providers = $this->locateValidProviders($providers, $isNewGroup);

        $this->process($providers, $isNewGroup);

        return $this;
    }

    /**
     * @param  Collection  $providers
     * @param  bool  $isNewGroup
     * @return void
     *
     * @throws Throwable
     */
    private function process(Collection $providers, bool $isNewGroup): void
    {
        $this->isChained()
            ? $this->handle($providers, $isNewGroup)
            : $this->database->transaction(fn () => $this->handle($providers, $isNewGroup));
    }

    /**
     * Execute all actions that must occur for
     * adding multiple participants to group.
     *
     * @param  Collection  $providers
     * @param  bool  $isNewGroup
     * @return void
     */
    private function handle(Collection $providers, bool $isNewGroup): void
    {
        $this->setData($this->storeManyParticipants($providers, $isNewGroup));

        // If we created any new participants, dispatch events / broadcast
        if ($this->getData()->count()) {
            if ($this->shouldExecuteChains()) {
                $this->fireBroadcast()->fireEvents();

                return;
            }

            $this->fireEvents();
        }
    }

    /**
     * Locate valid providers given alias/id key value pairs, if any.
     * Remove providers who are not friends with the current provider
     * when group friendship verification is required.
     *
     * @param  array|null  $providers
     * @param  bool  $isNewGroup
     * @return Collection
     */
    private function locateValidProviders(array $providers, bool $isNewGroup): Collection
    {
        if (! count($providers)
            || ($this->messenger->shouldVerifyGroupThreadFriendship()
                && ! $this->messenger->providerHasFriends())) {
            return Collection::make();
        }

        return Collection::make($providers)
            ->transform(fn (array $provider) => $this->getProvider($provider['alias'], $provider['id']))
            ->filter()
            ->when(
                $this->shouldVerifyFriendships(),
                fn (Collection $collection) => $this->rejectNonFriends($collection)
            )
            ->when(
                ! $isNewGroup,
                fn (Collection $collection) => $this->rejectExistingParticipants($collection)
            );
    }

    /**
     * @return bool
     */
    private function shouldVerifyFriendships(): bool
    {
        return $this->messenger->shouldVerifyGroupThreadFriendship()
            && $this->messenger->providerHasFriends();
    }

    /**
     * @param  Collection  $providers
     * @return Collection
     */
    private function rejectNonFriends(Collection $providers): Collection
    {
        return $providers->reject(
            fn (MessengerProvider $provider) => $this->friends->friendStatus($provider) !== FriendDriver::FRIEND
        );
    }

    /**
     * @param  Collection  $providers
     * @return Collection
     */
    private function rejectExistingParticipants(Collection $providers): Collection
    {
        $existing = $this->getThread()->participants()->get();

        return $providers->reject(
            fn (MessengerProvider $provider) => Helpers::forProviderInCollection($existing, $provider)->first()
        );
    }

    /**
     * @param  string  $alias
     * @param  string  $id
     * @return MessengerProvider|null
     */
    private function getProvider(string $alias, string $id): ?MessengerProvider
    {
        return $this->providersRepository->getProviderUsingAliasAndId($alias, $id);
    }

    /**
     * @param  Collection  $providers
     * @param  bool  $isNewGroup
     * @return Collection
     */
    private function storeManyParticipants(Collection $providers, bool $isNewGroup): Collection
    {
        if ($isNewGroup) {
            return $providers->transform(
                fn (MessengerProvider $provider) => $this->storeParticipant($provider, Participant::DefaultPermissions)
                    ->getParticipant(true)
            );
        }

        return $providers->transform(
            fn (MessengerProvider $provider) => $this->storeOrRestoreParticipant($provider)
                ->getParticipant(true)
        );
    }

    /**
     * @return array
     */
    private function generateBroadcastResource(): array
    {
        return (new NewThreadBroadcastResource(
            $this->messenger->getProvider(),
            $this->getThread(),
            false
        ))->resolve();
    }

    /**
     * @return $this
     */
    private function fireBroadcast(): self
    {
        if ($this->shouldFireBroadcast()) {
            $this->broadcaster
                ->toSelected($this->getData())
                ->with($this->generateBroadcastResource())
                ->broadcast(NewThreadBroadcast::class);
        }

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new ParticipantsAddedEvent(
                $this->messenger->getProvider(true),
                $this->getThread(true),
                $this->getData()
            ));
        }
    }
}
