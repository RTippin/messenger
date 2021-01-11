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
        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.friends.pending.index'))
            ->assertStatus(200)
            ->assertJsonCount(0);
    }

    /** @test */
    public function user_can_deny_pending_request()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        Event::fake([
            FriendDeniedBroadcast::class,
            FriendDeniedEvent::class,
        ]);

        $pending = PendingFriend::create([
            'sender_id' => $tippin->getKey(),
            'sender_type' => get_class($tippin),
            'recipient_id' => $doe->getKey(),
            'recipient_type' => get_class($doe),
        ]);

        $this->actingAs($doe);

        $this->deleteJson(route('api.messenger.friends.pending.destroy', [
            'pending' => $pending->id,
        ]))
            ->assertSuccessful();

        $this->assertDatabaseMissing('pending_friends', [
            'sender_id' => $tippin->getKey(),
            'sender_type' => get_class($tippin),
            'recipient_id' => $doe->getKey(),
            'recipient_type' => get_class($doe),
        ]);

        Event::assertDispatched(function (FriendDeniedBroadcast $event) use ($pending, $tippin) {
            $this->assertContains('private-user.'.$tippin->getKey(), $event->broadcastOn());
            $this->assertSame($pending->id, $event->broadcastWith()['sent_friend_id']);

            return true;
        });

        Event::assertDispatched(function (FriendDeniedEvent $event) use ($pending) {
            return $event->friend->id === $pending->id;
        });
    }

    /** @test */
    public function user_can_accept_pending_request()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        Event::fake([
            FriendApprovedBroadcast::class,
            FriendApprovedEvent::class,
        ]);

        $pending = SentFriend::create([
            'sender_id' => $tippin->getKey(),
            'sender_type' => get_class($tippin),
            'recipient_id' => $doe->getKey(),
            'recipient_type' => get_class($doe),
        ]);

        $this->actingAs($doe);

        $this->putJson(route('api.messenger.friends.pending.update', [
            'pending' => $pending->id,
        ]))
            ->assertSuccessful();

        $this->assertDatabaseMissing('pending_friends', [
            'sender_id' => $tippin->getKey(),
            'sender_type' => get_class($tippin),
            'recipient_id' => $doe->getKey(),
            'recipient_type' => get_class($doe),
        ]);

        $this->assertDatabaseHas('friends', [
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
            'party_id' => $doe->getKey(),
            'party_type' => get_class($doe),
        ]);

        $this->assertDatabaseHas('friends', [
            'owner_id' => $doe->getKey(),
            'owner_type' => get_class($doe),
            'party_id' => $tippin->getKey(),
            'party_type' => get_class($tippin),
        ]);

        $this->assertSame(1, resolve(FriendDriver::class)->friendStatus($tippin));

        Event::assertDispatched(function (FriendApprovedBroadcast $event) use ($tippin) {
            $this->assertContains('private-user.'.$tippin->getKey(), $event->broadcastOn());
            $this->assertSame('John Doe', $event->broadcastWith()['sender']['name']);

            return true;
        });

        Event::assertDispatched(function (FriendApprovedEvent $event) use ($tippin, $doe) {
            $this->assertEquals($doe->getKey(), $event->friend->owner_id);
            $this->assertEquals($tippin->getKey(), $event->inverseFriend->owner_id);

            return true;
        });
    }
}
