<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\UserModel;

class FriendsTest extends FeatureTestCase
{
    /** @test */
    public function test_guest_was_denied()
    {
        $this->get(route('api.messenger.friends.index'))
            ->assertUnauthorized();

        $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => 2,
            'recipient_alias' => 'user',
        ])
            ->assertUnauthorized();
    }

    /** @test */
    public function test_new_user_has_no_friends()
    {
        $this->actingAs(UserModel::first());

        $this->get(route('api.messenger.friends.index'))
            ->assertStatus(200)
            ->assertJsonCount(0);

        $this->get(route('api.messenger.friends.sent.index'))
            ->assertStatus(200)
            ->assertJsonCount(0);

        $this->get(route('api.messenger.friends.pending.index'))
            ->assertStatus(200)
            ->assertJsonCount(0);
    }

    /** @test */
    public function test_user_can_friend_another()
    {
        $this->actingAs(UserModel::first());

        $response = $this->postJson(route('api.messenger.friends.sent.store'), [
            'recipient_id' => 2,
            'recipient_alias' => 'user',
        ]);

        $response->assertStatus(201)
            ->assertSimilarJson([
                'sender_type' => 'RTippin\Messenger\Tests\UserModel'
            ]);
    }
}
