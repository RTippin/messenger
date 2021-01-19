<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\FriendCancelledBroadcast;
use RTippin\Messenger\Broadcasting\FriendRequestBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\FriendCancelledEvent;
use RTippin\Messenger\Events\FriendRequestEvent;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Tests\FeatureTestCase;

class SentFriendsTest extends FeatureTestCase
{
    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    private MessengerProvider $developers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->developers = $this->companyDevelopers();
    }

    /** @test */
    public function guest_is_unauthorized()
    {
        $this->getJson(route('api.messenger.friends.sent.index'))
            ->assertUnauthorized();
    }

    /** @test */
    public function new_user_has_no_sent_friends()
    {
        $this->actingAs($this->createJaneSmith());

        $this->getJson(route('api.messenger.friends.sent.index'))
            ->assertStatus(200)
            ->assertJsonCount(0);
    }

    /** @test */
    public function user_can_friend_another_user()
    {
        Event::fake([
            FriendRequestBroadcast::class,
            FriendRequestEvent::class,
        ]);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ])
            ->assertStatus(201)
            ->assertJson([
                'sender_id' => $this->tippin->getKey(),
                'sender_type' => get_class($this->tippin),
            ]);

        $this->assertDatabaseHas('pending_friends', [
            'sender_id' => $this->tippin->getKey(),
            'sender_type' => get_class($this->tippin),
            'recipient_id' => $this->doe->getKey(),
            'recipient_type' => get_class($this->doe),
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
    public function user_can_friend_another_company()
    {
        $this->expectsEvents([
            FriendRequestBroadcast::class,
            FriendRequestEvent::class,
        ]);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => $this->developers->getKey(),
            'recipient_alias' => 'company',
        ])
            ->assertStatus(201)
            ->assertJson([
                'sender_id' => $this->tippin->getKey(),
                'sender_type' => get_class($this->tippin),
            ]);
    }

    /** @test */
    public function user_cannot_friend_user_while_having_pending_sent()
    {
        $this->actingAs($this->tippin);

        SentFriend::create([
            'sender_id' => $this->tippin->getKey(),
            'sender_type' => get_class($this->tippin),
            'recipient_id' => $this->doe->getKey(),
            'recipient_type' => get_class($this->doe),
        ]);

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function user_can_cancel_sent_request()
    {
        $this->expectsEvents([
            FriendCancelledBroadcast::class,
            FriendCancelledEvent::class,
        ]);

        $sent = SentFriend::create([
            'sender_id' => $this->tippin->getKey(),
            'sender_type' => get_class($this->tippin),
            'recipient_id' => $this->doe->getKey(),
            'recipient_type' => get_class($this->doe),
        ]);

        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.friends.sent.destroy', [
            'sent' => $sent->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function user_cannot_friend_when_already_friends()
    {
        $this->createFriends($this->tippin, $this->doe);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ])
            ->assertForbidden();
    }
}
