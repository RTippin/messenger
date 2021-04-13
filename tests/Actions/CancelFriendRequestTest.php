<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Friends\CancelFriendRequest;
use RTippin\Messenger\Broadcasting\FriendCancelledBroadcast;
use RTippin\Messenger\Events\FriendCancelledEvent;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Tests\FeatureTestCase;

class CancelFriendRequestTest extends FeatureTestCase
{
    private SentFriend $sentFriend;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sentFriend = SentFriend::factory()->providers($this->tippin, $this->doe)->create();
    }

    /** @test */
    public function it_removes_sent_friend()
    {
        app(CancelFriendRequest::class)->withoutDispatches()->execute($this->sentFriend);

        $this->assertDatabaseMissing('pending_friends', [
            'id' => $this->sentFriend->id,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        Event::fake([
            FriendCancelledBroadcast::class,
            FriendCancelledEvent::class,
        ]);

        app(CancelFriendRequest::class)->execute($this->sentFriend);

        Event::assertDispatched(function (FriendCancelledBroadcast $event) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->sentFriend->id, $event->broadcastWith()['pending_friend_id']);

            return true;
        });
        Event::assertDispatched(function (FriendCancelledEvent $event) {
            return $this->sentFriend->id === $event->friend->id;
        });
    }
}
