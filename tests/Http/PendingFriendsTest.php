<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Tests\HttpTestCase;

class PendingFriendsTest extends HttpTestCase
{
    /** @test */
    public function user_has_no_pending_friends()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.friends.pending.index'))
            ->assertSuccessful()
            ->assertJsonCount(0);
    }

    /** @test */
    public function user_has_pending_friends()
    {
        $this->logCurrentRequest();
        PendingFriend::factory()->providers($this->doe, $this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.friends.pending.index'))
            ->assertSuccessful()
            ->assertJsonCount(1);
    }

    /** @test */
    public function user_can_view_pending_friend()
    {
        $this->logCurrentRequest();
        $pending = PendingFriend::factory()->providers($this->doe, $this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.friends.pending.show', [
            'pending' => $pending->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $pending->id,
            ]);
    }

    /** @test */
    public function user_can_deny_pending_request()
    {
        $this->logCurrentRequest();
        $pending = PendingFriend::factory()->providers($this->tippin, $this->doe)->create();
        $this->actingAs($this->doe);

        $this->deleteJson(route('api.messenger.friends.pending.destroy', [
            'pending' => $pending->id,
        ]))
            ->assertStatus(204);
    }

    /** @test */
    public function forbidden_to_deny_sent_request()
    {
        $this->logCurrentRequest();
        $sent = SentFriend::factory()->providers($this->tippin, $this->doe)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.friends.pending.destroy', [
            'pending' => $sent->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_can_accept_pending_request()
    {
        $this->logCurrentRequest();
        $pending = PendingFriend::factory()->providers($this->tippin, $this->doe)->create();
        $this->actingAs($this->doe);

        $this->putJson(route('api.messenger.friends.pending.update', [
            'pending' => $pending->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function forbidden_to_accept_sent_request()
    {
        $this->logCurrentRequest();
        $sent = SentFriend::factory()->providers($this->tippin, $this->doe)->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.friends.pending.update', [
            'pending' => $sent->id,
        ]))
            ->assertForbidden();
    }
}
