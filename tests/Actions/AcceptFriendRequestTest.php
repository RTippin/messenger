<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Friends\AcceptFriendRequest;
use RTippin\Messenger\Broadcasting\FriendApprovedBroadcast;
use RTippin\Messenger\Events\FriendApprovedEvent;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class AcceptFriendRequestTest extends FeatureTestCase
{
    use BroadcastLogger;

    /** @test */
    public function it_stores_friends()
    {
        $pending = PendingFriend::factory()->providers($this->doe, $this->tippin)->create();

        app(AcceptFriendRequest::class)->execute($pending);

        $this->assertDatabaseHas('friends', [
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'party_id' => $this->doe->getKey(),
            'party_type' => $this->doe->getMorphClass(),
        ]);
        $this->assertDatabaseHas('friends', [
            'owner_id' => $this->doe->getKey(),
            'owner_type' => $this->doe->getMorphClass(),
            'party_id' => $this->tippin->getKey(),
            'party_type' => $this->tippin->getMorphClass(),
        ]);
    }

    /** @test */
    public function it_removes_pending_friend()
    {
        $pending = PendingFriend::factory()->providers($this->doe, $this->tippin)->create();

        app(AcceptFriendRequest::class)->execute($pending);

        $this->assertDatabaseMissing('pending_friends', [
            'id' => $pending->id,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            FriendApprovedBroadcast::class,
            FriendApprovedEvent::class,
        ]);
        $pending = PendingFriend::factory()->providers($this->doe, $this->tippin)->create();

        app(AcceptFriendRequest::class)->execute($pending);

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
        $this->logBroadcast(FriendApprovedBroadcast::class);
    }
}
