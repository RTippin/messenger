<?php

namespace RTippin\Messenger\Actions\Friends;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\FriendCancelledBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\FriendCancelledEvent;
use RTippin\Messenger\Models\SentFriend;

class CancelFriendRequest extends BaseMessengerAction
{
    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var SentFriend
     */
    private SentFriend $sentFriend;

    /**
     * @var BroadcastDriver
     */
    private BroadcastDriver $broadcaster;

    /**
     * CancelFriendRequest constructor.
     *
     * @param  Dispatcher  $dispatcher
     * @param  BroadcastDriver  $broadcaster
     */
    public function __construct(Dispatcher $dispatcher, BroadcastDriver $broadcaster)
    {
        $this->dispatcher = $dispatcher;
        $this->broadcaster = $broadcaster;
    }

    /**
     * Cancel and destroy our sent friend request.
     *
     * @param  SentFriend  $sent
     * @return $this
     *
     * @throws Exception
     */
    public function execute(SentFriend $sent): self
    {
        $this->sentFriend = $sent;

        $this->destroySentFriend()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @return $this
     *
     * @throws Exception
     */
    private function destroySentFriend(): self
    {
        $this->sentFriend->delete();

        return $this;
    }

    /**
     * @return array
     */
    private function generateBroadcastResource(): array
    {
        return [
            'pending_friend_id' => $this->sentFriend->id,
        ];
    }

    /**
     * @return $this
     */
    private function fireBroadcast(): self
    {
        if ($this->shouldFireBroadcast()) {
            $this->broadcaster
                ->to($this->sentFriend->recipient)
                ->with($this->generateBroadcastResource())
                ->broadcast(FriendCancelledBroadcast::class);
        }

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new FriendCancelledEvent(
                $this->sentFriend->withoutRelations()
            ));
        }
    }
}
