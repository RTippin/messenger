<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\FriendCancelledBroadcast;
use RTippin\Messenger\Broadcasting\FriendRequestBroadcast;
use RTippin\Messenger\Events\FriendCancelledEvent;
use RTippin\Messenger\Events\FriendRequestEvent;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Tests\FeatureTestCase;

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
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        Event::fake([
            FriendRequestBroadcast::class,
            FriendRequestEvent::class,
        ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => $doe->getKey(),
            'recipient_alias' => 'user',
        ])
            ->assertStatus(201)
            ->assertJson([
                'sender_id' => $tippin->getKey(),
                'sender_type' => get_class($tippin),
            ]);

        $this->assertDatabaseHas('pending_friends', [
            'sender_id' => $tippin->getKey(),
            'sender_type' => get_class($tippin),
            'recipient_id' => $doe->getKey(),
            'recipient_type' => get_class($doe),
        ]);

        Event::assertDispatched(function (FriendRequestBroadcast $event) use ($tippin, $doe) {
            $this->assertContains('private-user.'.$doe->getKey(), $event->broadcastOn());
            $this->assertEquals($tippin->getKey(), $event->broadcastWith()['sender_id']);
            $this->assertEquals('Richard Tippin', $event->broadcastWith()['sender']['name']);

            return true;
        });

        Event::assertDispatched(function (FriendRequestEvent $event) use ($tippin, $doe) {
            $this->assertEquals($tippin->getKey(), $event->friend->sender_id);
            $this->assertEquals($doe->getKey(), $event->friend->recipient_id);
            $this->assertEquals(get_class($doe), $event->friend->recipient_type);

            return true;
        });
    }

    /** @test */
    public function user_can_friend_another_company()
    {
        $tippin = $this->userTippin();

        $developers = $this->companyDevelopers();

        Event::fake([
            FriendRequestBroadcast::class,
            FriendRequestEvent::class,
        ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => $developers->getKey(),
            'recipient_alias' => 'company',
        ])
            ->assertStatus(201)
            ->assertJson([
                'sender_id' => $tippin->getKey(),
                'sender_type' => get_class($tippin),
            ]);

        $this->assertDatabaseHas('pending_friends', [
            'sender_id' => $tippin->getKey(),
            'sender_type' => get_class($tippin),
            'recipient_id' => $developers->getKey(),
            'recipient_type' => get_class($developers),
        ]);

        Event::assertDispatched(function (FriendRequestBroadcast $event) use ($tippin, $developers) {
            $this->assertContains('private-company.'.$developers->getKey(), $event->broadcastOn());
            $this->assertEquals($tippin->getKey(), $event->broadcastWith()['sender_id']);
            $this->assertEquals('Richard Tippin', $event->broadcastWith()['sender']['name']);

            return true;
        });

        Event::assertDispatched(function (FriendRequestEvent $event) use ($tippin, $developers) {
            $this->assertEquals($tippin->getKey(), $event->friend->sender_id);
            $this->assertEquals($developers->getKey(), $event->friend->recipient_id);
            $this->assertEquals(get_class($developers), $event->friend->recipient_type);

            return true;
        });
    }

    /** @test */
    public function user_cannot_friend_user_while_having_pending_sent()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $this->doesntExpectEvents([
            FriendRequestBroadcast::class,
            FriendRequestEvent::class,
        ]);

        $this->actingAs($tippin);

        SentFriend::create([
            'sender_id' => $tippin->getKey(),
            'sender_type' => get_class($tippin),
            'recipient_id' => $doe->getKey(),
            'recipient_type' => get_class($doe),
        ]);

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => $doe->getKey(),
            'recipient_alias' => 'user',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function user_can_cancel_sent_request()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        Event::fake([
            FriendCancelledBroadcast::class,
            FriendCancelledEvent::class,
        ]);

        $sent = SentFriend::create([
            'sender_id' => $tippin->getKey(),
            'sender_type' => get_class($tippin),
            'recipient_id' => $doe->getKey(),
            'recipient_type' => get_class($doe),
        ]);

        $this->actingAs($tippin);

        $this->deleteJson(route('api.messenger.friends.sent.destroy', [
            'sent' => $sent->id,
        ]))
            ->assertSuccessful();

        $this->assertDatabaseMissing('pending_friends', [
            'sender_id' => $tippin->getKey(),
            'sender_type' => get_class($tippin),
            'recipient_id' => $doe->getKey(),
            'recipient_type' => get_class($doe),
        ]);

        Event::assertDispatched(function (FriendCancelledBroadcast $event) use ($sent, $doe) {
            $this->assertContains('private-user.'.$doe->getKey(), $event->broadcastOn());
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
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $this->doesntExpectEvents([
            FriendRequestBroadcast::class,
            FriendRequestEvent::class,
        ]);

        $this->makeFriends(
            $tippin,
            $doe
        );

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => $doe->getKey(),
            'recipient_alias' => 'user',
        ])
            ->assertForbidden();
    }
}
