<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Friends\CancelFriendRequest;
use RTippin\Messenger\Broadcasting\FriendCancelledBroadcast;
use RTippin\Messenger\Events\FriendCancelledEvent;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class CancelFriendRequestTest extends FeatureTestCase
{
    use BroadcastLogger;

    /** @test */
    public function it_removes_sent_friend()
    {
        $sent = SentFriend::factory()->providers($this->tippin, $this->doe)->create();

        app(CancelFriendRequest::class)->withoutDispatches()->execute($sent);

        $this->assertDatabaseMissing('pending_friends', [
            'id' => $sent->id,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            FriendCancelledBroadcast::class,
            FriendCancelledEvent::class,
        ]);
        $sent = SentFriend::factory()->providers($this->tippin, $this->doe)->create();

        app(CancelFriendRequest::class)->execute($sent);

        Event::assertDispatched(function (FriendCancelledBroadcast $event) use ($sent) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($sent->id, $event->broadcastWith()['pending_friend_id']);

            return true;
        });
        Event::assertDispatched(function (FriendCancelledEvent $event) use ($sent) {
            return $sent->id === $event->friend->id;
        });
        $this->logBroadcast(FriendCancelledBroadcast::class);
    }
}
