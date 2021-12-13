<?php

namespace RTippin\Messenger\Actions\Friends;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\FriendApprovedBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\FriendApprovedEvent;
use RTippin\Messenger\Http\Resources\Broadcast\FriendApprovedBroadcastResource;
use RTippin\Messenger\Http\Resources\FriendResource;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\PendingFriend;
use Throwable;

class AcceptFriendRequest extends BaseMessengerAction
{
    /**
     * @var PendingFriend
     */
    private PendingFriend $pending;

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
     * @var Friend
     */
    private Friend $friend;

    /**
     * @var Friend
     */
    private Friend $inverseFriend;

    /**
     * AcceptFriendRequest constructor.
     *
     * @param  DatabaseManager  $database
     * @param  BroadcastDriver  $broadcaster
     * @param  Dispatcher  $dispatcher
     */
    public function __construct(DatabaseManager $database,
                                BroadcastDriver $broadcaster,
                                Dispatcher $dispatcher)
    {
        $this->database = $database;
        $this->broadcaster = $broadcaster;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Accept the pending friend request. We will remove the pending model
     * and create two mirrored friend models to link our friendship!
     *
     * @param  PendingFriend  $pending
     * @return $this
     *
     * @throws Throwable
     */
    public function execute(PendingFriend $pending): self
    {
        $this->pending = $pending;

        $this->process()
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents();

        return $this;
    }

    /**
     * @return $this
     *
     * @throws Throwable
     */
    private function process(): self
    {
        $this->isChained()
            ? $this->handle()
            : $this->database->transaction(fn () => $this->handle());

        return $this;
    }

    /**
     * Execute transactions.
     *
     * @return void
     *
     * @throws Exception
     */
    private function handle(): void
    {
        $this->storeMyFriend();

        $this->storeInverseFriend();

        $this->destroyPending();
    }

    /**
     * Store friend relationship.
     *
     * @return void
     */
    private function storeMyFriend(): void
    {
        $this->friend = Friend::create([
            'owner_id' => $this->pending->recipient_id,
            'owner_type' => $this->pending->recipient_type,
            'party_id' => $this->pending->sender_id,
            'party_type' => $this->pending->sender_type,
        ])
            ->setRelations([
                'owner' => $this->pending->recipient,
                'party' => $this->pending->sender,
            ]);
    }

    /**
     * Store inverse friend relationship.
     *
     * @return void
     */
    private function storeInverseFriend(): void
    {
        $this->inverseFriend = Friend::create([
            'owner_id' => $this->pending->sender_id,
            'owner_type' => $this->pending->sender_type,
            'party_id' => $this->pending->recipient_id,
            'party_type' => $this->pending->recipient_type,
        ])
            ->setRelations([
                'owner' => $this->pending->sender,
                'party' => $this->pending->recipient,
            ]);
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    private function destroyPending(): void
    {
        $this->pending->delete();
    }

    /**
     * @return $this
     */
    private function generateResource(): self
    {
        $this->setJsonResource(new FriendResource(
            $this->friend
        ));

        return $this;
    }

    /**
     * @return array
     */
    private function generateBroadcastResource(): array
    {
        return (new FriendApprovedBroadcastResource(
            $this->inverseFriend
        ))->resolve();
    }

    /**
     * @return $this
     */
    private function fireBroadcast(): self
    {
        if ($this->shouldFireBroadcast()) {
            $this->broadcaster
                ->to($this->pending->sender)
                ->with($this->generateBroadcastResource())
                ->broadcast(FriendApprovedBroadcast::class);
        }

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new FriendApprovedEvent(
                $this->friend->withoutRelations(),
                $this->inverseFriend->withoutRelations()
            ));
        }
    }
}
