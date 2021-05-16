<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Tests\FeatureTestCase;

class SentFriendsTest extends FeatureTestCase
{
    /** @test */
    public function user_has_no_sent_friends()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.friends.sent.index'))
            ->assertStatus(200)
            ->assertJsonCount(0);
    }

    /** @test */
    public function user_can_friend_another_user()
    {
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ])
            ->assertStatus(201)
            ->assertJson([
                'sender_id' => $this->tippin->getKey(),
                'sender_type' => $this->tippin->getMorphClass(),
            ]);
    }

    /** @test */
    public function user_can_friend_another_company()
    {
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => $this->developers->getKey(),
            'recipient_alias' => 'company',
        ])
            ->assertStatus(201)
            ->assertJson([
                'sender_id' => $this->tippin->getKey(),
                'sender_type' => $this->tippin->getMorphClass(),
            ]);
    }

    /** @test */
    public function user_cannot_friend_user_while_having_pending_sent()
    {
        SentFriend::factory()->providers($this->tippin, $this->doe)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function user_can_cancel_sent_request()
    {
        $sent = SentFriend::factory()->providers($this->tippin, $this->doe)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.friends.sent.destroy', [
            'sent' => $sent->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function user_cannot_friend_when_already_friends()
    {
        $this->createFriends($this->tippin, $this->doe);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => $this->doe->getKey(),
            'recipient_alias' => 'user',
        ])
            ->assertForbidden();
    }
}
