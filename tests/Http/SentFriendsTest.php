<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\FriendCancelledBroadcast;
use RTippin\Messenger\Broadcasting\FriendRequestBroadcast;
use RTippin\Messenger\Events\FriendCancelledEvent;
use RTippin\Messenger\Events\FriendRequestEvent;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\stubs\UserModel;

class SentFriendsTest extends FeatureTestCase
{
    /** @test */
    public function guest_is_unauthorized()
    {
        $this->getJson(route('api.messenger.friends.sent.index'))
            ->assertUnauthorized();
    }

    /** @test */
    public function new_user_has_no_sent_friends()
    {
        $this->actingAs(UserModel::find(1));

        $this->getJson(route('api.messenger.friends.sent.index'))
            ->assertStatus(200)
            ->assertJsonCount(0);
    }

    /** @test */
    public function user_can_friend_another()
    {
        $this->expectsEvents([
            FriendRequestBroadcast::class,
            FriendRequestEvent::class,
        ]);

        $this->actingAs(UserModel::find(1));

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => 2,
            'recipient_alias' => 'user',
        ])
            ->assertStatus(201)
            ->assertJson([
                'sender_id' => 1,
            ]);

        $this->assertDatabaseHas('pending_friends', [
            'sender_id' => 1,
            'recipient_id' => 2,
        ]);
    }

    /** @test */
    public function user_cannot_friend_user_while_having_pending_sent()
    {
        $this->doesntExpectEvents([
            FriendRequestBroadcast::class,
            FriendRequestEvent::class,
        ]);

        $this->actingAs(UserModel::find(1));

        SentFriend::create([
            'sender_id' => 1,
            'sender_type' => self::UserModelType,
            'recipient_id' => 2,
            'recipient_type' => self::UserModelType,
        ]);

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => 2,
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
            'sender_id' => 1,
            'sender_type' => self::UserModelType,
            'recipient_id' => 2,
            'recipient_type' => self::UserModelType,
        ]);

        $this->actingAs(UserModel::find(1));

        $this->deleteJson(route('api.messenger.friends.sent.destroy', [
            'sent' => $sent->getKey(),
        ]))
            ->assertSuccessful();

        $this->assertDatabaseMissing('pending_friends', [
            'sender_id' => 1,
            'recipient_id' => 2,
        ]);
    }

    /** @test */
    public function user_cannot_friend_when_already_friends()
    {
        $this->doesntExpectEvents([
            FriendRequestBroadcast::class,
            FriendRequestEvent::class,
        ]);

        $this->actingAs(UserModel::find(1));

        Friend::create([
            'owner_id' => 1,
            'owner_type' => self::UserModelType,
            'party_id' => 2,
            'party_type' => self::UserModelType,
        ]);

        Friend::create([
            'owner_id' => 2,
            'owner_type' => self::UserModelType,
            'party_id' => 1,
            'party_type' => self::UserModelType,
        ]);

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => 2,
            'recipient_alias' => 'user',
        ])
            ->assertForbidden();
    }
}
