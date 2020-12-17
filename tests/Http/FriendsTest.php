<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\FriendApprovedBroadcast;
use RTippin\Messenger\Broadcasting\FriendRequestBroadcast;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Events\FriendApprovedEvent;
use RTippin\Messenger\Events\FriendRequestEvent;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\UserModel;

class FriendsTest extends FeatureTestCase
{
    /** @test */
    public function test_guest_was_denied()
    {
        $this->get(route('api.messenger.friends.index'))
            ->assertUnauthorized();

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => 2,
            'recipient_alias' => 'user',
        ])
            ->assertUnauthorized();
    }

    /** @test */
    public function test_new_user_has_no_friends()
    {
        $this->actingAs(UserModel::first());

        $this->get(route('api.messenger.friends.index'))
            ->assertStatus(200)
            ->assertJsonCount(0);

        $this->get(route('api.messenger.friends.sent.index'))
            ->assertStatus(200)
            ->assertJsonCount(0);

        $this->get(route('api.messenger.friends.pending.index'))
            ->assertStatus(200)
            ->assertJsonCount(0);
    }

    /** @test */
    public function test_user_can_friend_another()
    {
        $users = UserModel::all();

        $this->actingAs($users->first());

        $this->expectsEvents([
            FriendRequestBroadcast::class,
            FriendRequestEvent::class,
        ]);

        $response = $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => $users->last()->id,
            'recipient_alias' => 'user',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'sender_type' => 'RTippin\Messenger\Tests\UserModel',
            ]);

        $this->assertDatabaseHas('pending_friends', [
            'sender_id' => $users->first()->id,
            'recipient_id' => $users->last()->id,
        ]);
    }

    /** @test */
    public function test_user_cannot_friend_user_already_sent()
    {
        $users = UserModel::all();

        $this->actingAs($users->first());

        SentFriend::create([
            'sender_id' => $users->first()->id,
            'sender_type' => 'RTippin\Messenger\Tests\UserModel',
            'recipient_id' => $users->last()->id,
            'recipient_type' => 'RTippin\Messenger\Tests\UserModel',
        ]);

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => $users->last()->id,
            'recipient_alias' => 'user',
        ])->assertForbidden();
    }

    /** @test */
    public function test_user_can_cancel_sent_request()
    {
        $users = UserModel::all();

        $sent = SentFriend::create([
            'sender_id' => $users->first()->id,
            'sender_type' => 'RTippin\Messenger\Tests\UserModel',
            'recipient_id' => $users->last()->id,
            'recipient_type' => 'RTippin\Messenger\Tests\UserModel',
        ]);

        $this->assertDatabaseHas('pending_friends', [
            'sender_id' => $users->first()->id,
            'recipient_id' => $users->last()->id,
        ]);

        $this->actingAs($users->first());

        $this->deleteJson(route('api.messenger.friends.sent.destroy', [
            'sent' => $sent->id,
        ]))->assertSuccessful();

        $this->assertDatabaseMissing('pending_friends', [
            'sender_id' => $users->first()->id,
            'recipient_id' => $users->last()->id,
        ]);
    }

    /** @test */
    public function test_user_can_deny_pending_request()
    {
        $users = UserModel::all();

        $pending = PendingFriend::create([
            'sender_id' => $users->first()->id,
            'sender_type' => 'RTippin\Messenger\Tests\UserModel',
            'recipient_id' => $users->last()->id,
            'recipient_type' => 'RTippin\Messenger\Tests\UserModel',
        ]);

        $this->assertDatabaseHas('pending_friends', [
            'sender_id' => $users->first()->id,
            'recipient_id' => $users->last()->id,
        ]);

        $this->actingAs($users->first());

        $this->deleteJson(route('api.messenger.friends.pending.destroy', [
            'pending' => $pending->id,
        ]))->assertForbidden();

        $this->actingAs($users->last());

        $this->deleteJson(route('api.messenger.friends.pending.destroy', [
            'pending' => $pending->id,
        ]))->assertSuccessful();

        $this->assertDatabaseMissing('pending_friends', [
            'sender_id' => $users->first()->id,
            'recipient_id' => $users->last()->id,
        ]);
    }

    /** @test */
    public function test_user_can_accept_pending_request()
    {
        $users = UserModel::all();

        $friends = resolve(FriendDriver::class);

        $this->expectsEvents([
            FriendApprovedBroadcast::class,
            FriendApprovedEvent::class,
        ]);

        $pending = SentFriend::create([
            'sender_id' => $users->first()->id,
            'sender_type' => 'RTippin\Messenger\Tests\UserModel',
            'recipient_id' => $users->last()->id,
            'recipient_type' => 'RTippin\Messenger\Tests\UserModel',
        ]);

        $this->actingAs($users->first());

        $this->putJson(route('api.messenger.friends.pending.update', [
            'pending' => $pending->id,
        ]))->assertForbidden();

        $this->actingAs($users->last());

        $this->putJson(route('api.messenger.friends.pending.update', [
            'pending' => $pending->id,
        ]))->assertSuccessful();

        $this->assertDatabaseMissing('pending_friends', [
            'sender_id' => $users->first()->id,
            'recipient_id' => $users->last()->id,
        ]);

        $this->assertDatabaseHas('friends', [
            'owner_id' => $users->first()->id,
            'party_id' => $users->last()->id,
        ]);

        $this->assertDatabaseHas('friends', [
            'owner_id' => $users->last()->id,
            'party_id' => $users->first()->id,
        ]);

        $this->assertEquals($friends->friendStatus($users->first()), 1);
    }
}
