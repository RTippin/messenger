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
     * StoreFriendRequest constructor.
     *
     * @param Messenger $messenger
     * @param ProvidersRepository $providersRepository
     * @param BroadcastDriver $broadcaster
     * @param Dispatcher $dispatcher
     * @param FriendDriver $friends
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
     * @param mixed ...$parameters
     * @var FriendRequest[0]
     * @return $this
     * @throws FriendException|ProviderNotFoundException
     */
    public function execute(...$parameters): self
    {
        $this->locateRecipientProvider(
            $parameters[0]['recipient_alias'],
            $parameters[0]['recipient_id']
        )
            ->recipientIsValid()
            ->storeSentFriendRequest()
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @param string $alias
     * @param string $id
     * @return $this
     */
    private function locateRecipientProvider(string $alias, string $id): self
    {
        $this->recipient = $this->providersRepository->getProviderUsingAliasAndId($alias, $id);

        return $this;
    }

    /**
     * @return $this
     * @throws FriendException|ProviderNotFoundException
     * @noinspection PhpParamsInspection
     */
    private function recipientIsValid(): self
    {
        if (is_null($this->recipient)) {
            throw new ProviderNotFoundException;
        } elseif ($this->messenger->getProvider()->is($this->recipient)) {
            throw new FriendException('Cannot friend yourself.');
        } elseif (! $this->messenger->canFriendProvider($this->recipient)) {
            throw new FriendException('Not authorized to add friend.');
        } elseif ($this->friends->friendStatus($this->recipient) !== 0) {
            throw new FriendException("You are already friends, or have a pending request with {$this->recipient->name()}.");
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function storeSentFriendRequest(): self
    {
        $this->setData(
            SentFriend::create([
                'sender_id' => $this->messenger->getProviderId(),
                'sender_type' => $this->messenger->getProviderClass(),
                'recipient_id' => $this->recipient->getKey(),
                'recipient_type' => get_class($this->recipient),
            ])
                ->setRelations([
                    'recipient' => $this->recipient,
                    'sender' => $this->messenger->getProvider(),
                ])
        );

        return $this;
    }

    /**
     * @return $this
     */
    private function generateResource(): self
    {
        $this->setJsonResource(new SentFriendResource(
            $this->getData()
        ));

        return $this;
    }

    /**
     * @return array
     */
    private function generateBroadcastResource(): array
    {
        return (new FriendRequestBroadcastResource(
            $this->getData()
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
                $this->getData(true)
            ));
        }
    }
}
