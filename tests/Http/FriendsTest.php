<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Tests\HttpTestCase;

class FriendsTest extends HttpTestCase
{
    /** @test */
    public function user_has_no_friends()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.friends.index'))
            ->assertStatus(200)
            ->assertJsonCount(0);
    }

    /** @test */
    public function user_has_friends()
    {
        $this->logCurrentRequest();
        $this->createFriends($this->tippin, $this->doe);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.friends.index'))
            ->assertStatus(200)
            ->assertJsonCount(1);
    }

    /** @test */
    public function user_can_remove_friend()
    {
        $this->logCurrentRequest();
        $friend = Friend::factory()->providers($this->tippin, $this->doe)->create();
        Friend::factory()->providers($this->doe, $this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.friends.destroy', [
            'friend' => $friend->id,
        ]))
            ->assertStatus(204);
    }

    /** @test */
    public function user_can_view_friend()
    {
        $this->logCurrentRequest();
        $friend = Friend::factory()->providers($this->tippin, $this->doe)->create();
        Friend::factory()->providers($this->doe, $this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.friends.show', [
            'friend' => $friend->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $friend->id,
            ]);
    }

    /** @test */
    public function user_cannot_remove_inverse_friend()
    {
        $this->logCurrentRequest();
        Friend::factory()->providers($this->tippin, $this->doe)->create();
        $inverse = Friend::factory()->providers($this->doe, $this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.friends.destroy', [
            'friend' => $inverse->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_cannot_view_inverse_friend()
    {
        Friend::factory()->providers($this->tippin, $this->doe)->create();
        $inverse = Friend::factory()->providers($this->doe, $this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.friends.show', [
            'friend' => $inverse->id,
        ]))
            ->assertForbidden();
    }
}
