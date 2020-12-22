<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Definitions;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\UserModel;

class ThreadsTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setupInitialThreads();
    }

    private function setupInitialThreads(): void
    {
        $users = UserModel::all();

        $group = Thread::create([
            'type' => 2,
            'subject' => 'Test Group',
            'image' => '1.png',
            'add_participants' => true,
            'invitations' => true,
        ]);

        $group->participants()
            ->create(array_merge(Definitions::DefaultAdminParticipant, [
                'owner_id' => $users->first()->getKey(),
                'owner_type' => get_class($users->first()),
            ]));

        $group->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => $users->last()->getKey(),
                'owner_type' => get_class($users->last()),
            ]));

        $private = Thread::create(Definitions::DefaultThread);

        $private->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => $users->first()->getKey(),
                'owner_type' => get_class($users->first()),
            ]));

        $private->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => $users->last()->getKey(),
                'owner_type' => get_class($users->last()),
            ]));
    }

    /** @test */
    public function test_guest_is_unauthorized()
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
        $user = UserModel::create([
            'name' => 'Jane Smith',
            'email' => 'smith@example.net',
            'password' => 'secret',
        ]);

        $this->actingAs($user);

        $this->get(route('api.messenger.threads.index'))
            ->assertStatus(200)
            ->assertJsonCount(0, 'data')
            ->assertJsonFragment([
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

    /** @test */
    public function test_user_belongs_to_two_threads()
    {
        $this->actingAs(UserModel::first());

        $this->get(route('api.messenger.threads.index'))
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }
}
