<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\FriendApprovedBroadcast;
use RTippin\Messenger\Broadcasting\FriendDeniedBroadcast;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Events\FriendApprovedEvent;
use RTippin\Messenger\Events\FriendDeniedEvent;
use RTippin\Messenger\Models\PendingFriend;
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
    public function user_has_no_pending_friends()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.friends.pending.index'))
            ->assertStatus(200)
            ->assertJsonCount(0);
    }

    /** @test */
    public function user_can_deny_pending_request()
    {
        $pending = PendingFriend::factory()->providers($this->tippin, $this->doe)->create();
        $this->actingAs($this->doe);

        $this->expectsEvents([
            FriendDeniedBroadcast::class,
            FriendDeniedEvent::class,
        ]);

        $this->deleteJson(route('api.messenger.friends.pending.destroy', [
            'pending' => $pending->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function user_can_accept_pending_request()
    {
        $pending = PendingFriend::factory()->providers($this->tippin, $this->doe)->create();
        $this->actingAs($this->doe);

        $this->expectsEvents([
            FriendApprovedBroadcast::class,
            FriendApprovedEvent::class,
        ]);

        $this->putJson(route('api.messenger.friends.pending.update', [
            'pending' => $pending->id,
        ]))
            ->assertSuccessful();

        $this->assertSame(1, resolve(FriendDriver::class)->friendStatus($this->tippin));
    }
}
