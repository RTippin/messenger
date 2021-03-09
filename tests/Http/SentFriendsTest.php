<?php

namespace RTippin\Messenger\Tests\Http;

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
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            FriendRequestBroadcast::class,
            FriendRequestEvent::class,
        ]);

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ])
            ->assertStatus(201)
            ->assertJson([
                'sender_id' => $this->tippin->getKey(),
                'sender_type' => get_class($this->tippin),
            ]);
    }

    /** @test */
    public function user_can_friend_another_company()
    {
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            FriendRequestBroadcast::class,
            FriendRequestEvent::class,
        ]);

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
        SentFriend::create([
            'sender_id' => $this->tippin->getKey(),
            'sender_type' => get_class($this->tippin),
            'recipient_id' => $this->doe->getKey(),
            'recipient_type' => get_class($this->doe),
        ]);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function user_can_cancel_sent_request()
    {
        $sent = SentFriend::create([
            'sender_id' => $this->tippin->getKey(),
            'sender_type' => get_class($this->tippin),
            'recipient_id' => $this->doe->getKey(),
            'recipient_type' => get_class($this->doe),
        ]);
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            FriendCancelledBroadcast::class,
            FriendCancelledEvent::class,
        ]);

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
