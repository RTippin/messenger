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
    public function guest_is_unauthorized()
    {
        $this->getJson(route('api.messenger.threads.index'))
            ->assertUnauthorized();
    }

    /** @test */
    public function new_user_has_no_threads()
    {
        $user = UserModel::create([
            'name' => 'Jane Smith',
            'email' => 'smith@example.net',
            'password' => 'secret',
        ]);

        $this->actingAs($user);

        $this->getJson(route('api.messenger.threads.index'))
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
    public function user_belongs_to_two_threads()
    {
        $users = UserModel::all();

        $this->actingAs($users->first());

        $this->getJson(route('api.messenger.threads.index'))
            ->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJson([
                'data' => [
                    [
                        'type_verbose' => 'GROUP',
                        'name' => 'Test Group',
                    ],
                    [
                        'type_verbose' => 'PRIVATE',
                        'name' => $users->last()->name(),
                    ],
                ],
            ]);
    }

    /** @test */
    public function invalid_thread_id_not_found()
    {
        $this->actingAs(UserModel::first());

        $this->getJson(route('api.messenger.threads.show', [
            'thread' => '123456-789',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function user_forbidden_to_view_thread_they_do_not_belong_to()
    {
        $group = Thread::create([
            'type' => 2,
            'subject' => 'Empty Group',
            'image' => '2.png',
            'add_participants' => true,
            'invitations' => true,
        ]);

        $this->actingAs(UserModel::first());

        $this->getJson(route('api.messenger.threads.show', [
            'thread' => $group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function view_individual_private_thread()
    {
        $users = UserModel::all();

        $thread = Thread::private()->first();

        $this->actingAs($users->first());

        $this->getJson(route('api.messenger.threads.show', [
            'thread' => $thread->id,
        ]))
            ->assertStatus(200)
            ->assertJson([
                'id' => $thread->id,
                'type' => 1,
                'type_verbose' => 'PRIVATE',
                'group' => false,
                'unread' => true,
                'name' => $users->last()->name(),
                'options' => [
                    'add_participants' => false,
                    'admin' => false,
                    'invitations' => false,
                ],
                'resources' => [
                    'recipient' => [
                        'provider_id' => $users->last()->getKey(),
                        'name' => $users->last()->name(),
                    ],
                ],
            ]);
    }

    /** @test */
    public function view_individual_group_thread()
    {
        $users = UserModel::all();

        $thread = Thread::group()->first();

        $this->actingAs($users->first());

        $this->getJson(route('api.messenger.threads.show', [
            'thread' => $thread->id,
        ]))
            ->assertStatus(200)
            ->assertJson([
                'id' => $thread->id,
                'type' => 2,
                'type_verbose' => 'GROUP',
                'group' => true,
                'unread' => true,
                'name' => 'Test Group',
                'options' => [
                    'add_participants' => true,
                    'admin' => true,
                    'invitations' => true,
                ],
            ]);
    }
}
