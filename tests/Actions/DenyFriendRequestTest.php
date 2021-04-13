<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Friends\DenyFriendRequest;
use RTippin\Messenger\Broadcasting\FriendDeniedBroadcast;
use RTippin\Messenger\Events\FriendDeniedEvent;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Tests\FeatureTestCase;

class DenyFriendRequestTest extends FeatureTestCase
{
    private PendingFriend $pendingFriend;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pendingFriend = PendingFriend::factory()->providers($this->doe, $this->tippin)->create();
    }

    /** @test */
    public function it_removes_pending_friend()
    {
        app(DenyFriendRequest::class)->withoutDispatches()->execute($this->pendingFriend);

        $this->assertDatabaseMissing('pending_friends', [
            'id' => $this->pendingFriend->id,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        Event::fake([
            FriendDeniedBroadcast::class,
            FriendDeniedEvent::class,
        ]);

        app(DenyFriendRequest::class)->execute($this->pendingFriend);

        Event::assertDispatched(function (FriendDeniedBroadcast $event) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->pendingFriend->id, $event->broadcastWith()['sent_friend_id']);

            return true;
        });

        Event::assertDispatched(function (FriendDeniedEvent $event) {
            return $this->pendingFriend->id === $event->friend->id;
        });
    }
}
