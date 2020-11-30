<?php

namespace RTippin\Messenger\Actions\Friends;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RTippin\Messenger\Actions\Base\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\FriendRequestBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\FriendRequestEvent;
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
     * StoreFriendRequest constructor.
     *
     * @param Messenger $messenger
     * @param ProvidersRepository $providersRepository
     * @param BroadcastDriver $broadcaster
     * @param Dispatcher $dispatcher
     */
    public function __construct(Messenger $messenger,
                                ProvidersRepository $providersRepository,
                                BroadcastDriver $broadcaster,
                                Dispatcher $dispatcher)
    {
        $this->messenger = $messenger;
        $this->providersRepository = $providersRepository;
        $this->broadcaster = $broadcaster;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Store our new sent friend request and notify the recipient!
     *
     * @param mixed ...$parameters
     * @var FriendRequest $validated $parameters[0]
     * @return $this
     * @throws AuthorizationException|ModelNotFoundException
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
        $this->recipient = $this->providersRepository
            ->getProviderUsingAliasAndId($alias, $id);

        return $this;
    }

    /**
     * @return $this
     * @throws AuthorizationException
     * @noinspection PhpParamsInspection
     */
    private function recipientIsValid(): self
    {
        if(is_null($this->recipient)
            || $this->messenger->getProvider()->is($this->recipient))
        {
            $this->throwProviderNotFoundError();
        }

        if( ! $this->messenger->canFriendProvider($this->recipient)
            || $this->messenger->getProvider()->friendStatus($this->recipient) !== 0)
        {
            $this->throwAuthorizationError();
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
                'recipient_type' => get_class($this->recipient)
            ])
                ->setRelations([
                    'recipient' => $this->recipient,
                    'sender' => $this->messenger->getProvider()
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
    private function generateBroadcastResource()
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
        if($this->shouldFireBroadcast())
        {
            $this->broadcaster
                ->to($this->recipient)
                ->with($this->generateBroadcastResource())
                ->broadcast(FriendRequestBroadcast::class);
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function fireEvents(): self
    {
        if($this->shouldFireEvents())
        {
            $this->dispatcher->dispatch(new FriendRequestEvent(
                $this->getData(true)
            ));
        }

        return $this;
    }

    /**
     * @throws ModelNotFoundException
     */
    private function throwProviderNotFoundError()
    {
        throw new ModelNotFoundException;
    }

    /**
     * @throws AuthorizationException
     */
    private function throwAuthorizationError()
    {
        throw new AuthorizationException("Not authorized to add friend.");
    }
}