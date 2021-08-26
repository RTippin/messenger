<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Friends\DenyFriendRequest;
use RTippin\Messenger\Broadcasting\FriendDeniedBroadcast;
use RTippin\Messenger\Events\FriendDeniedEvent;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class DenyFriendRequestTest extends FeatureTestCase
{
    use BroadcastLogger;

    /** @test */
    public function it_removes_pending_friend()
    {
        $pending = PendingFriend::factory()->providers($this->doe, $this->tippin)->create();

        app(DenyFriendRequest::class)->withoutDispatches()->execute($pending);

        $this->assertDatabaseMissing('pending_friends', [
            'id' => $pending->id,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            FriendDeniedBroadcast::class,
            FriendDeniedEvent::class,
        ]);
        $pending = PendingFriend::factory()->providers($this->doe, $this->tippin)->create();

        app(DenyFriendRequest::class)->execute($pending);

        Event::assertDispatched(function (FriendDeniedBroadcast $event) use ($pending) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($pending->id, $event->broadcastWith()['sent_friend_id']);

            return true;
        });
        Event::assertDispatched(function (FriendDeniedEvent $event) use ($pending) {
            return $pending->id === $event->friend->id;
        });
        $this->logBroadcast(FriendDeniedBroadcast::class);
    }
}
