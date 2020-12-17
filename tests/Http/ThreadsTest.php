<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\UserModel;

class ThreadsTest extends FeatureTestCase
{
    /** @test */
    public function test_unauthenticated_was_denied()
    {
        $this->get(route('api.messenger.threads.index'))
            ->assertUnauthorized();

        $this->post(route('api.messenger.privates.store'), [
            'recipient_id' => 2,
            'recipient_alias' => 'user',
            'message' => 'Hello!',
        ])
            ->assertUnauthorized();
    }

    /** @test */
    public function test_new_user_has_no_threads()
    {
        $user = UserModel::first();

        $this->actingAs($user);

        $response = $this->get(route('api.messenger.threads.index'));

        $response->assertStatus(200);

        $response->assertJsonFragment([
            'data' => [],
        ]);

        $response->assertJsonFragment([
            'meta' => [
                'final_page' => true,
                'index' => true,
                'next_page_id' => null,
                'next_page_route' => null,
                'page_id' => null,
                'per_page' => Messenger::getThreadsIndexCount(),
                'results' => 0,
                'total' => 0,
            ],
        ]);
    }

}