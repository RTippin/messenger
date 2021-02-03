<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Friends\AcceptFriendRequest;
use RTippin\Messenger\Broadcasting\FriendApprovedBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\FriendApprovedEvent;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Tests\FeatureTestCase;

class AcceptFriendRequestTest extends FeatureTestCase
{
    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    private PendingFriend $pendingFriend;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->pendingFriend = PendingFriend::create([
            'sender_id' => $this->doe->getKey(),
            'sender_type' => get_class($this->doe),
            'recipient_id' => $this->tippin->getKey(),
            'recipient_type' => get_class($this->tippin),
        ]);
    }

    /** @test */
    public function accept_friend_stores_friends()
    {
        app(AcceptFriendRequest::class)->withoutDispatches()->execute($this->pendingFriend);

        $this->assertDatabaseHas('friends', [
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'party_id' => $this->doe->getKey(),
            'party_type' => get_class($this->doe),
        ]);

        $this->assertDatabaseHas('friends', [
            'owner_id' => $this->doe->getKey(),
            'owner_type' => get_class($this->doe),
            'party_id' => $this->tippin->getKey(),
            'party_type' => get_class($this->tippin),
        ]);
    }

    /** @test */
    public function accept_friend_removes_pending_friend()
    {
        app(AcceptFriendRequest::class)->withoutDispatches()->execute($this->pendingFriend);

        $this->assertDatabaseMissing('pending_friends', [
            'id' => $this->pendingFriend->id,
        ]);
    }

    /** @test */
    public function accept_friend_fires_events()
    {
        Event::fake([
            FriendApprovedBroadcast::class,
            FriendApprovedEvent::class,
        ]);

        app(AcceptFriendRequest::class)->execute($this->pendingFriend);

        Event::assertDispatched(function (FriendApprovedBroadcast $event) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame('Richard Tippin', $event->broadcastWith()['sender']['name']);

            return true;
        });

        Event::assertDispatched(function (FriendApprovedEvent $event) {
            $this->assertEquals($this->tippin->getKey(), $event->friend->owner_id);
            $this->assertEquals($this->doe->getKey(), $event->inverseFriend->owner_id);

            return true;
        });
    }
}
