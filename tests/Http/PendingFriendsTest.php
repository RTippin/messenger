<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\FriendApprovedBroadcast;
use RTippin\Messenger\Broadcasting\FriendDeniedBroadcast;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Events\FriendApprovedEvent;
use RTippin\Messenger\Events\FriendDeniedEvent;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\stubs\UserModel;

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
        Event::fake([
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
            'sender_type' => self::UserModelType,
            'recipient_id' => 2,
            'recipient_type' => self::UserModelType,
        ]);

        Event::assertDispatched(function (FriendDeniedBroadcast $event) use ($pending) {
            $this->assertContains('private-user.1', $event->broadcastOn());
            $this->assertEquals($pending->id, $event->broadcastWith()['sent_friend_id']);

            return true;
        });

        Event::assertDispatched(function (FriendDeniedEvent $event) use ($pending) {
            return $event->friend->id === $pending->id;
        });
    }

    /** @test */
    public function user_can_accept_pending_request()
    {
        Event::fake([
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
            'sender_type' => self::UserModelType,
            'recipient_id' => 2,
            'recipient_type' => self::UserModelType,
        ]);

        $this->assertDatabaseHas('friends', [
            'owner_id' => 1,
            'owner_type' => self::UserModelType,
            'party_id' => 2,
            'party_type' => self::UserModelType,
        ]);

        $this->assertDatabaseHas('friends', [
            'owner_id' => 2,
            'owner_type' => self::UserModelType,
            'party_id' => 1,
            'party_type' => self::UserModelType,
        ]);

        $this->assertEquals(1, resolve(FriendDriver::class)->friendStatus(UserModel::find(1)));

        Event::assertDispatched(function (FriendApprovedBroadcast $event) use ($pending) {
            $this->assertContains('private-user.1', $event->broadcastOn());
            $this->assertEquals('John Doe', $event->broadcastWith()['sender']['name']);

            return true;
        });

        Event::assertDispatched(function (FriendApprovedEvent $event) {
            $this->assertEquals(2, $event->friend->owner_id);
            $this->assertEquals(1, $event->inverseFriend->owner_id);

            return true;
        });
    }
}
