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
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\ProvidersRepository;
use RTippin\Messenger\Support\Definitions;
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
     * @param Messenger $messenger
     * @param BroadcastDriver $broadcaster
     * @param Dispatcher $dispatcher
     * @param ProvidersRepository $providersRepository
     * @param DatabaseManager $database
     * @param FriendDriver $friends
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
     * @param mixed ...$parameters
     * @var Thread[0]
     * @var array[1]
     * @var bool|null[2]
     * @return $this
     * @throws Throwable
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0]);

        $isNewGroup = $parameters[2] ?? false;

        $providers = $this->locateValidProviders(
            $parameters[1], $isNewGroup
        );

        $this->handleTransactions($providers, $isNewGroup);

        return $this;
    }

    /**
     * @param Collection $providers
     * @param bool $isNewGroup
     * @return $this
     * @throws Throwable
     */
    private function handleTransactions(Collection $providers, bool $isNewGroup): self
    {
        if ($this->isChained()) {
            $this->executeTransactions($providers, $isNewGroup);
        } else {
            $this->database->transaction(fn () => $this->executeTransactions($providers, $isNewGroup));
        }

        return $this;
    }

    /**
     * Execute all actions that must occur for
     * adding multiple participants to group.
     *
     * @param Collection $providers
     * @param bool $isNewGroup
     */
    private function executeTransactions(Collection $providers, bool $isNewGroup): void
    {
        $this->setData(
            $this->storeManyParticipants(
                $providers, $isNewGroup
            )
        );

        //If we created any new participants, dispatch events / broadcast

        if ($this->getData()->count()) {
            if ($this->shouldExecuteChains()) {
                $this->fireBroadcast()->fireEvents();
            } else {
                $this->fireEvents();
            }
        }
    }

    /**
     * Locate valid providers given alias/id key value pairs, if any. Remove null
     * results and providers who are not friends with the master set provider.
     *
     * @param array|null $providers
     * @param bool $isNewGroup
     * @return Collection
     */
    private function locateValidProviders(array $providers, bool $isNewGroup): Collection
    {
        if ($this->messenger->providerHasFriends() && count($providers)) {
            $providers = collect($providers)
                ->transform(fn (array $provider) => $this->getProvider($provider['alias'], $provider['id']))
                ->filter(fn ($provider) => ! is_null($provider))
                ->reject(fn (MessengerProvider $provider) => $this->friends->friendStatus($provider) !== 1);

            return $isNewGroup
                ? $providers
                : $this->rejectExistingParticipants($providers);
        }

        return collect();
    }

    /**
     * @param Collection $providers
     * @return Collection
     */
    private function rejectExistingParticipants(Collection $providers): Collection
    {
        $existing = $this->getThread()->participants()->get();

        return $providers->reject(
            fn (MessengerProvider $provider) => $existing
                ->where('owner_id', '=', $provider->getKey())
                ->where('owner_type', '=', get_class($provider))
                ->first()
        );
    }

    /**
     * @param string $alias
     * @param string $id
     * @return MessengerProvider|null
     */
    private function getProvider(string $alias, string $id): ?MessengerProvider
    {
        return $this->providersRepository
            ->getProviderUsingAliasAndId($alias, $id);
    }

    /**
     * @param Collection $providers
     * @param bool $isNewGroup
     * @return Collection
     */
    private function storeManyParticipants(Collection $providers, bool $isNewGroup): Collection
    {
        if ($isNewGroup) {
            return $providers->transform(
                fn (MessengerProvider $provider) => $this->storeParticipant($provider, Definitions::DefaultParticipant)
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
     * @return $this
     */
    private function fireEvents(): self
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new ParticipantsAddedEvent(
                $this->messenger->getProvider()->withoutRelations(),
                $this->getThread(true),
                $this->getData()
            ));
        }

        return $this;
    }
}
