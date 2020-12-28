<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\FriendApprovedBroadcast;
use RTippin\Messenger\Broadcasting\FriendCancelledBroadcast;
use RTippin\Messenger\Broadcasting\FriendDeniedBroadcast;
use RTippin\Messenger\Broadcasting\FriendRequestBroadcast;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Events\FriendApprovedEvent;
use RTippin\Messenger\Events\FriendCancelledEvent;
use RTippin\Messenger\Events\FriendDeniedEvent;
use RTippin\Messenger\Events\FriendRemovedEvent;
use RTippin\Messenger\Events\FriendRequestEvent;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\UserModel;

class FriendsTest extends FeatureTestCase
{
    /** @test */
    public function guest_is_unauthorized()
    {
        $this->getJson(route('api.messenger.friends.index'))
            ->assertUnauthorized();

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => 2,
            'recipient_alias' => 'user',
        ])
            ->assertUnauthorized();
    }

    /** @test */
    public function new_user_has_no_friends()
    {
        $this->actingAs(UserModel::first());

        $this->getJson(route('api.messenger.friends.index'))
            ->assertStatus(200)
            ->assertJsonCount(0);

        $this->getJson(route('api.messenger.friends.sent.index'))
            ->assertStatus(200)
            ->assertJsonCount(0);

        $this->getJson(route('api.messenger.friends.pending.index'))
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

        $users = UserModel::all();

        $this->actingAs($users->first());

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => $users->last()->getKey(),
            'recipient_alias' => 'user',
        ])
            ->assertStatus(201)
            ->assertJson([
                'sender_id' => $users->first()->getKey(),
            ]);

        $this->assertDatabaseHas('pending_friends', [
            'sender_id' => $users->first()->getKey(),
            'recipient_id' => $users->last()->getKey(),
        ]);
    }

    /** @test */
    public function user_cannot_friend_user_while_having_pending_sent()
    {
        $this->doesntExpectEvents([
            FriendRequestBroadcast::class,
            FriendRequestEvent::class,
        ]);

        $users = UserModel::all();

        $this->actingAs($users->first());

        SentFriend::create([
            'sender_id' => $users->first()->getKey(),
            'sender_type' => get_class($users->first()),
            'recipient_id' => $users->last()->getKey(),
            'recipient_type' => get_class($users->last()),
        ]);

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => $users->last()->getKey(),
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

        $users = UserModel::all();

        $sent = SentFriend::create([
            'sender_id' => $users->first()->getKey(),
            'sender_type' => get_class($users->first()),
            'recipient_id' => $users->last()->getKey(),
            'recipient_type' => get_class($users->last()),
        ]);

        $this->actingAs($users->first());

        $this->deleteJson(route('api.messenger.friends.sent.destroy', [
            'sent' => $sent->getKey(),
        ]))
            ->assertSuccessful();

        $this->assertDatabaseMissing('pending_friends', [
            'sender_id' => $users->first()->getKey(),
            'recipient_id' => $users->last()->getKey(),
        ]);
    }

    /** @test */
    public function user_can_deny_pending_request()
    {
        $this->expectsEvents([
            FriendDeniedBroadcast::class,
            FriendDeniedEvent::class,
        ]);

        $users = UserModel::all();

        $pending = PendingFriend::create([
            'sender_id' => $users->first()->getKey(),
            'sender_type' => get_class($users->first()),
            'recipient_id' => $users->last()->getKey(),
            'recipient_type' => get_class($users->last()),
        ]);

        $this->actingAs($users->last());

        $this->deleteJson(route('api.messenger.friends.pending.destroy', [
            'pending' => $pending->getKey(),
        ]))
            ->assertSuccessful();

        $this->assertDatabaseMissing('pending_friends', [
            'sender_id' => $users->first()->getKey(),
            'recipient_id' => $users->last()->getKey(),
        ]);
    }

    /** @test */
    public function user_can_accept_pending_request()
    {
        $this->expectsEvents([
            FriendApprovedBroadcast::class,
            FriendApprovedEvent::class,
        ]);

        $users = UserModel::all();

        $friends = resolve(FriendDriver::class);

        $pending = SentFriend::create([
            'sender_id' => $users->first()->getKey(),
            'sender_type' => get_class($users->first()),
            'recipient_id' => $users->last()->getKey(),
            'recipient_type' => get_class($users->last()),
        ]);

        $this->actingAs($users->last());

        $this->putJson(route('api.messenger.friends.pending.update', [
            'pending' => $pending->getKey(),
        ]))
            ->assertSuccessful();

        $this->assertDatabaseMissing('pending_friends', [
            'sender_id' => $users->first()->getKey(),
            'recipient_id' => $users->last()->getKey(),
        ]);

        $this->assertDatabaseHas('friends', [
            'owner_id' => $users->first()->getKey(),
            'party_id' => $users->last()->getKey(),
        ]);

        $this->assertDatabaseHas('friends', [
            'owner_id' => $users->last()->getKey(),
            'party_id' => $users->first()->getKey(),
        ]);

        $this->assertEquals(1, $friends->friendStatus($users->first()));
    }

    /** @test */
    public function user_can_remove_friend()
    {
        $this->expectsEvents([
            FriendRemovedEvent::class,
        ]);

        $users = UserModel::all();

        $friends = resolve(FriendDriver::class);

        $friend = Friend::create([
            'owner_id' => $users->first()->getKey(),
            'owner_type' => get_class($users->first()),
            'party_id' => $users->last()->getKey(),
            'party_type' => get_class($users->last()),
        ]);

        Friend::create([
            'owner_id' => $users->last()->getKey(),
            'owner_type' => get_class($users->last()),
            'party_id' => $users->first()->getKey(),
            'party_type' => get_class($users->first()),
        ]);

        $this->actingAs($users->first());

        $this->deleteJson(route('api.messenger.friends.destroy', [
            'friend' => $friend->getKey(),
        ]))
            ->assertSuccessful();

        $this->assertDatabaseMissing('friends', [
            'owner_id' => $users->first()->getKey(),
            'party_id' => $users->last()->getKey(),
        ]);

        $this->assertDatabaseMissing('friends', [
            'owner_id' => $users->last()->getKey(),
            'party_id' => $users->first()->getKey(),
        ]);

        $this->assertEquals(0, $friends->friendStatus($users->first()));
    }

    /** @test */
    public function user_cannot_friend_when_already_friends()
    {
        $this->doesntExpectEvents([
            FriendRequestBroadcast::class,
            FriendRequestEvent::class,
        ]);

        $users = UserModel::all();

        $this->actingAs($users->first());

        Friend::create([
            'owner_id' => $users->first()->getKey(),
            'owner_type' => get_class($users->first()),
            'party_id' => $users->last()->getKey(),
            'party_type' => get_class($users->last()),
        ]);

        Friend::create([
            'owner_id' => $users->last()->getKey(),
            'owner_type' => get_class($users->last()),
            'party_id' => $users->first()->getKey(),
            'party_type' => get_class($users->first()),
        ]);

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => $users->last()->getKey(),
            'recipient_alias' => 'user',
        ])
            ->assertForbidden();
    }
}
