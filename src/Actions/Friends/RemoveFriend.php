<?php

namespace RTippin\Messenger\Actions\Friends;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\FriendRemovedBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\FriendRemovedEvent;
use RTippin\Messenger\Http\Resources\ProviderResource;
use RTippin\Messenger\Models\Friend;
use Throwable;

class RemoveFriend extends BaseMessengerAction
{
    /**
     * @var BroadcastDriver
     */
    private BroadcastDriver $broadcaster;

    /**
     * @var DatabaseManager
     */
    private DatabaseManager $database;

    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var Friend
     */
    private Friend $friend;

    /**
     * @var Friend|null
     */
    private ?Friend $inverseFriend;

    /**
     * RemoveFriend constructor.
     *
     * @param  BroadcastDriver  $broadcaster
     * @param  DatabaseManager  $database
     * @param  Dispatcher  $dispatcher
     */
    public function __construct(BroadcastDriver $broadcaster,
                                DatabaseManager $database,
                                Dispatcher $dispatcher)
    {
        $this->broadcaster = $broadcaster;
        $this->database = $database;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Remove and destroy our friend relationship. This will
     * remove our friend model and its inverse/mirrored model.
     *
     * @param  Friend  $friend
     * @return $this
     *
     * @throws Exception|Throwable
     */
    public function execute(Friend $friend): self
    {
        $this->friend = $friend;

        $this->setInverseFriend()
            ->process()
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
     * @return $this
     */
    private function generateResource(): self
    {
        $this->setJsonResource(new ProviderResource(
            $this->friend->party
        ));

        return $this;
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    private function handle(): void
    {
        if (! is_null($this->inverseFriend)) {
            $this->inverseFriend->delete();
        }

        $this->friend->delete();
    }

    /**
     * @return $this
     */
    private function setInverseFriend(): self
    {
        $this->inverseFriend = Friend::forProviderWithModel($this->friend, 'party')
            ->forProviderWithModel($this->friend, 'owner', 'party')
            ->first();

        return $this;
    }

    /**
     * @return $this
     */
    private function fireBroadcast(): self
    {
        if ($this->shouldFireBroadcast()
            && ! is_null($this->inverseFriend)) {
            $this->broadcaster
                ->to($this->inverseFriend)
                ->with(['friend_id' => $this->inverseFriend->id])
                ->broadcast(FriendRemovedBroadcast::class);
        }

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new FriendRemovedEvent(
                $this->friend->withoutRelations(),
                optional($this->inverseFriend)->withoutRelations()
            ));
        }
    }
}
