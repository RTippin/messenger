<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Friends\CancelFriendRequest;
use RTippin\Messenger\Broadcasting\FriendCancelledBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\FriendCancelledEvent;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Tests\FeatureTestCase;

class CancelFriendRequestTest extends FeatureTestCase
{
    private MessengerProvider $doe;

    private SentFriend $sentFriend;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->sentFriend = SentFriend::create([
            'sender_id' => $tippin->getKey(),
            'sender_type' => get_class($tippin),
            'recipient_id' => $this->doe->getKey(),
            'recipient_type' => get_class($this->doe),
        ]);
    }

    /** @test */
    public function cancel_request_removes_sent_friend()
    {
        app(CancelFriendRequest::class)->withoutDispatches()->execute($this->sentFriend);

        $this->assertDatabaseMissing('pending_friends', [
            'id' => $this->sentFriend->id,
        ]);
    }

    /** @test */
    public function user_can_cancel_sent_request()
    {
        Event::fake([
            FriendCancelledBroadcast::class,
            FriendCancelledEvent::class,
        ]);

        app(CancelFriendRequest::class)->execute($this->sentFriend);

        Event::assertDispatched(function (FriendCancelledBroadcast $event) {
            $this->assertContains('private-user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->sentFriend->id, $event->broadcastWith()['pending_friend_id']);

            return true;
        });

        Event::assertDispatched(function (FriendCancelledEvent $event) {
            return $this->sentFriend->id === $event->friend->id;
        });
    }
}
