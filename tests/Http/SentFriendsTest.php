<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\FriendCancelledBroadcast;
use RTippin\Messenger\Broadcasting\FriendRequestBroadcast;
use RTippin\Messenger\Events\FriendCancelledEvent;
use RTippin\Messenger\Events\FriendRequestEvent;
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
        $this->actingAs($this->generateJaneSmith());

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

        $this->actingAs(UserModel::find(1));

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => 2,
            'recipient_alias' => 'user',
        ])
            ->assertStatus(201)
            ->assertJson([
                'sender_id' => 1,
                'sender_type' => self::UserModelType,
            ]);

        $this->assertDatabaseHas('pending_friends', [
            'sender_id' => 1,
            'sender_type' => self::UserModelType,
            'recipient_id' => 2,
            'recipient_type' => self::UserModelType,
        ]);

        Event::assertDispatched(function (FriendRequestBroadcast $event) {
            $this->assertContains('private-user.2', $event->broadcastOn());
            $this->assertEquals(1, $event->broadcastWith()['sender_id']);
            $this->assertEquals('Richard Tippin', $event->broadcastWith()['sender']['name']);

            return true;
        });

        Event::assertDispatched(function (FriendRequestEvent $event) {
            $this->assertEquals(1, $event->friend->sender_id);
            $this->assertEquals(2, $event->friend->recipient_id);
            $this->assertEquals(self::UserModelType, $event->friend->recipient_type);

            return true;
        });
    }

    /** @test */
    public function user_can_friend_another_company()
    {
        Event::fake([
            FriendRequestBroadcast::class,
            FriendRequestEvent::class,
        ]);

        $this->actingAs(UserModel::find(1));

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => 1,
            'recipient_alias' => 'company',
        ])
            ->assertStatus(201)
            ->assertJson([
                'sender_id' => 1,
                'sender_type' => self::UserModelType,
            ]);

        $this->assertDatabaseHas('pending_friends', [
            'sender_id' => 1,
            'sender_type' => self::UserModelType,
            'recipient_id' => 1,
            'recipient_type' => self::CompanyModelType,
        ]);

        Event::assertDispatched(function (FriendRequestBroadcast $event) {
            $this->assertContains('private-company.1', $event->broadcastOn());
            $this->assertEquals(1, $event->broadcastWith()['sender_id']);
            $this->assertEquals('Richard Tippin', $event->broadcastWith()['sender']['name']);

            return true;
        });

        Event::assertDispatched(function (FriendRequestEvent $event) {
            $this->assertEquals(1, $event->friend->sender_id);
            $this->assertEquals(1, $event->friend->recipient_id);
            $this->assertEquals(self::CompanyModelType, $event->friend->recipient_type);

            return true;
        });
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
        Event::fake([
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
            'sent' => $sent->id,
        ]))
            ->assertSuccessful();

        $this->assertDatabaseMissing('pending_friends', [
            'sender_id' => 1,
            'sender_type' => self::UserModelType,
            'recipient_id' => 2,
            'recipient_type' => self::UserModelType,
        ]);

        Event::assertDispatched(function (FriendCancelledBroadcast $event) use ($sent) {
            $this->assertContains('private-user.2', $event->broadcastOn());
            $this->assertEquals($sent->id, $event->broadcastWith()['pending_friend_id']);

            return true;
        });

        Event::assertDispatched(function (FriendCancelledEvent $event) use ($sent) {
            return $sent->id === $event->friend->id;
        });
    }

    /** @test */
    public function user_cannot_friend_when_already_friends()
    {
        $this->doesntExpectEvents([
            FriendRequestBroadcast::class,
            FriendRequestEvent::class,
        ]);

        $this->makeFriends(
            UserModel::find(1),
            UserModel::find(2)
        );

        $this->actingAs(UserModel::find(1));

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => 2,
            'recipient_alias' => 'user',
        ])
            ->assertForbidden();
    }
}
