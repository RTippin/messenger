<?php

namespace RTippin\Messenger\Actions\Friends;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\FriendRequestBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\FriendRequestEvent;
use RTippin\Messenger\Exceptions\FriendException;
use RTippin\Messenger\Exceptions\ProviderNotFoundException;
use RTippin\Messenger\Http\Request\FriendRequest;
use RTippin\Messenger\Http\Resources\Broadcast\FriendRequestBroadcastResource;
use RTippin\Messenger\Http\Resources\SentFriendResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Repositories\ProvidersRepository;

class StoreFriendRequest extends BaseMessengerAction
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
     * @var BroadcastDriver
     */
    private BroadcastDriver $broadcaster;

    /**
     * @var MessengerProvider|null
     */
    private ?MessengerProvider $recipient;

    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var FriendDriver
     */
    private FriendDriver $friends;

    /**
     * @var SentFriend
     */
    private SentFriend $sentFriend;

    /**
     * StoreFriendRequest constructor.
     *
     * @param  Messenger  $messenger
     * @param  ProvidersRepository  $providersRepository
     * @param  BroadcastDriver  $broadcaster
     * @param  Dispatcher  $dispatcher
     * @param  FriendDriver  $friends
     */
    public function __construct(Messenger $messenger,
                                ProvidersRepository $providersRepository,
                                BroadcastDriver $broadcaster,
                                Dispatcher $dispatcher,
                                FriendDriver $friends)
    {
        $this->messenger = $messenger;
        $this->providersRepository = $providersRepository;
        $this->broadcaster = $broadcaster;
        $this->dispatcher = $dispatcher;
        $this->friends = $friends;
    }

    /**
     * Store our new sent friend request and notify the recipient!
     *
     * @param  array  $params
     * @return $this
     *
     * @see FriendRequest
     *
     * @throws FriendException|ProviderNotFoundException
     */
    public function execute(array $params): self
    {
        $this->locateAndSetRecipientProvider(
            $params['recipient_alias'],
            $params['recipient_id']
        );

        $this->bailIfChecksFail();

        $this->storeSentFriendRequest()
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @param  string  $alias
     * @param  string  $id
     */
    private function locateAndSetRecipientProvider(string $alias, string $id): void
    {
        $this->recipient = $this->providersRepository->getProviderUsingAliasAndId($alias, $id);
    }

    /**
     * @throws FriendException|ProviderNotFoundException
     * @noinspection PhpParamsInspection
     */
    private function bailIfChecksFail(): void
    {
        if (is_null($this->recipient)) {
            throw new ProviderNotFoundException;
        }

        if ($this->messenger->getProvider()->is($this->recipient)) {
            throw new FriendException('Cannot friend yourself.');
        }

        if (! $this->messenger->canFriendProvider($this->recipient)) {
            throw new FriendException('Not authorized to add friend.');
        }

        if ($this->friends->friendStatus($this->recipient) !== FriendDriver::NOT_FRIEND) {
            throw new FriendException("You are already friends, or have a pending request with {$this->recipient->getProviderName()}.");
        }
    }

    /**
     * @return $this
     */
    private function storeSentFriendRequest(): self
    {
        $this->sentFriend = SentFriend::create([
            'sender_id' => $this->messenger->getProvider()->getKey(),
            'sender_type' => $this->messenger->getProvider()->getMorphClass(),
            'recipient_id' => $this->recipient->getKey(),
            'recipient_type' => $this->recipient->getMorphClass(),
        ])
            ->setRelations([
                'recipient' => $this->recipient,
                'sender' => $this->messenger->getProvider(),
            ]);

        return $this;
    }

    /**
     * @return $this
     */
    private function generateResource(): self
    {
        $this->setJsonResource(new SentFriendResource(
            $this->sentFriend
        ));

        return $this;
    }

    /**
     * @return array
     */
    private function generateBroadcastResource(): array
    {
        return (new FriendRequestBroadcastResource(
            $this->sentFriend
        ))->resolve();
    }

    /**
     * @return $this
     */
    private function fireBroadcast(): self
    {
        if ($this->shouldFireBroadcast()) {
            $this->broadcaster
                ->to($this->recipient)
                ->with($this->generateBroadcastResource())
                ->broadcast(FriendRequestBroadcast::class);
        }

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new FriendRequestEvent(
                $this->sentFriend->withoutRelations()
            ));
        }
    }
}
