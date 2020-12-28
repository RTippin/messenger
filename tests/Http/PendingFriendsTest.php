<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\FriendApprovedBroadcast;
use RTippin\Messenger\Broadcasting\FriendDeniedBroadcast;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Events\FriendApprovedEvent;
use RTippin\Messenger\Events\FriendDeniedEvent;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\UserModel;

class PendingFriendsTest extends FeatureTestCase
{
    /** @test */
    public function guest_is_unauthorized()
    {
        $this->getJson(route('api.messenger.friends.pending.index'))
            ->assertUnauthorized();
    }

    /** @test */
    public function new_user_has_no_pending_friends()
    {
        $this->actingAs(UserModel::first());

        $this->getJson(route('api.messenger.friends.pending.index'))
            ->assertStatus(200)
            ->assertJsonCount(0);
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
}
