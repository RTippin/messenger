<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Friends\StoreFriendRequest;
use RTippin\Messenger\Broadcasting\FriendRequestBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\FriendRequestEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreFriendRequestTest extends FeatureTestCase
{
    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function store_friend_request_stores_sent_friend()
    {
        app(StoreFriendRequest::class)->withoutDispatches()->execute([
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ]);

        $this->assertDatabaseHas('pending_friends', [
            'sender_id' => $this->tippin->getKey(),
            'sender_type' => get_class($this->tippin),
            'recipient_id' => $this->doe->getKey(),
            'recipient_type' => get_class($this->doe),
        ]);
    }

    /** @test */
    public function store_friend_request_fires_events()
    {
        Event::fake([
            FriendRequestBroadcast::class,
            FriendRequestEvent::class,
        ]);

        app(StoreFriendRequest::class)->execute([
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ]);

        Event::assertDispatched(function (FriendRequestBroadcast $event) {
            $this->assertContains('private-user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->tippin->getKey(), $event->broadcastWith()['sender_id']);
            $this->assertSame('Richard Tippin', $event->broadcastWith()['sender']['name']);

            return true;
        });

        Event::assertDispatched(function (FriendRequestEvent $event) {
            $this->assertSame($this->tippin->getKey(), $event->friend->sender_id);
            $this->assertSame($this->doe->getKey(), $event->friend->recipient_id);

            return true;
        });
    }

    /** @test */
    public function store_friend_throws_exception_when_invalid_provider()
    {
        $this->expectException(ModelNotFoundException::class);

        app(StoreFriendRequest::class)->withoutDispatches()->execute([
            'recipient_id' => 404,
            'recipient_alias' => 'user',
        ]);
    }

    /** @test */
    public function store_friend_throws_exception_when_already_friends()
    {
        $this->expectException(AuthorizationException::class);

        $this->createFriends($this->tippin, $this->doe);

        app(StoreFriendRequest::class)->withoutDispatches()->execute([
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ]);
    }

    /** @test */
    public function store_friend_throws_exception_when_is_sent_friend()
    {
        $this->expectException(AuthorizationException::class);

        SentFriend::create([
            'sender_id' => $this->tippin->getKey(),
            'sender_type' => get_class($this->tippin),
            'recipient_id' => $this->doe->getKey(),
            'recipient_type' => get_class($this->doe),
        ]);

        app(StoreFriendRequest::class)->withoutDispatches()->execute([
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ]);
    }

    /** @test */
    public function store_friend_throws_exception_when_is_pending_friend()
    {
        $this->expectException(AuthorizationException::class);

        PendingFriend::create([
            'sender_id' => $this->doe->getKey(),
            'sender_type' => get_class($this->doe),
            'recipient_id' => $this->tippin->getKey(),
            'recipient_type' => get_class($this->tippin),
        ]);

        app(StoreFriendRequest::class)->withoutDispatches()->execute([
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ]);
    }
}
