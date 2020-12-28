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
        $this->actingAs(UserModel::find(1));

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

        $pending = PendingFriend::create([
            'sender_id' => 1,
            'sender_type' => self::UserModelType,
            'recipient_id' => 2,
            'recipient_type' => self::UserModelType,
        ]);

        $this->actingAs(UserModel::find(2));

        $this->deleteJson(route('api.messenger.friends.pending.destroy', [
            'pending' => $pending->id,
        ]))
            ->assertSuccessful();

        $this->assertDatabaseMissing('pending_friends', [
            'sender_id' => 1,
            'recipient_id' => 2,
        ]);
    }

    /** @test */
    public function user_can_accept_pending_request()
    {
        $this->expectsEvents([
            FriendApprovedBroadcast::class,
            FriendApprovedEvent::class,
        ]);

        $pending = SentFriend::create([
            'sender_id' => 1,
            'sender_type' => self::UserModelType,
            'recipient_id' => 2,
            'recipient_type' => self::UserModelType,
        ]);

        $this->actingAs(UserModel::find(2));

        $this->putJson(route('api.messenger.friends.pending.update', [
            'pending' => $pending->id,
        ]))
            ->assertSuccessful();

        $this->assertDatabaseMissing('pending_friends', [
            'sender_id' => 1,
            'recipient_id' => 2,
        ]);

        $this->assertDatabaseHas('friends', [
            'owner_id' => 1,
            'party_id' => 2,
        ]);

        $this->assertDatabaseHas('friends', [
            'owner_id' => 2,
            'party_id' => 1,
        ]);

        $this->assertEquals(1, resolve(FriendDriver::class)->friendStatus(UserModel::find(1)));
    }
}
