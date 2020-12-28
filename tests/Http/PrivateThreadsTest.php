<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\NewThreadBroadcast;
use RTippin\Messenger\Broadcasting\ThreadApprovalBroadcast;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Events\NewThreadEvent;
use RTippin\Messenger\Events\ThreadApprovalEvent;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\UserModel;

class PrivateThreadsTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setupInitialThreads();
    }

    private function setupInitialThreads(): void
    {
        $users = UserModel::all();

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
        $this->getJson(route('api.messenger.privates.index'))
            ->assertUnauthorized();

        $this->postJson(route('api.messenger.privates.store'), [
            'recipient_id' => 2,
            'recipient_alias' => 'user',
            'message' => 'Hello!',
        ])
            ->assertUnauthorized();
    }

    /** @test */
    public function private_thread_locator_returns_not_found_on_invalid_user()
    {
        $this->actingAs(UserModel::first());

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => 'user',
            'id' => 404,
        ]))
            ->assertNotFound();

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => 'unknown',
            'id' => 2,
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function private_thread_locator_returns_user_with_existing_thread_id()
    {
        $users = UserModel::all();

        $this->actingAs($users->first());

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => 'user',
            'id' => $users->last()->getKey(),
        ]))
            ->assertStatus(200)
            ->assertJson([
                'thread_id' => Thread::private()->first()->id,
                'recipient' => [
                    'provider_id' => $users->last()->getKey(),
                    'name' => $users->last()->name(),
                ],
            ]);
    }

    /** @test */
    public function private_thread_locator_returns_user_without_existing_thread_id()
    {
        $otherUser = UserModel::create([
            'name' => 'Jane Smith',
            'email' => 'smith@example.net',
            'password' => 'secret',
        ]);

        $this->actingAs(UserModel::first());

        $this->getJson(route('api.messenger.privates.locate', [
            'alias' => 'user',
            'id' => $otherUser->getKey(),
        ]))
            ->assertStatus(200)
            ->assertJson([
                'thread_id' => null,
                'recipient' => [
                    'provider_id' => $otherUser->getKey(),
                    'name' => $otherUser->name(),
                ],
            ]);
    }

    /** @test */
    public function creating_new_private_thread_with_non_friend_is_pending()
    {
        $this->expectsEvents([
            NewThreadBroadcast::class,
            NewThreadEvent::class,
        ]);

        $myself = UserModel::first();

        $otherUser = UserModel::create([
            'name' => 'Jane Smith',
            'email' => 'smith@example.net',
            'password' => 'secret',
        ]);

        $this->actingAs($myself);

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $otherUser->getKey(),
        ])
            ->assertSuccessful()
            ->assertJson([
                'type' => 1,
                'type_verbose' => 'PRIVATE',
                'pending' => true,
                'group' => false,
                'unread' => true,
                'name' => $otherUser->name(),
                'options' => [
                    'awaiting_my_approval' => false,
                ],
                'resources' => [
                    'latest_message' => [
                        'body' => 'Hello World!',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $otherUser->getKey(),
            'pending' => true,
        ]);
    }

    /** @test */
    public function creating_new_private_thread_with_friend_is_not_pending()
    {
        $this->expectsEvents([
            NewThreadBroadcast::class,
            NewThreadEvent::class,
        ]);

        $myself = UserModel::first();

        $otherUser = UserModel::create([
            'name' => 'Jane Smith',
            'email' => 'smith@example.net',
            'password' => 'secret',
        ]);

        Friend::create([
            'owner_id' => $myself->getKey(),
            'owner_type' => get_class($myself),
            'party_id' => $otherUser->getKey(),
            'party_type' => get_class($otherUser),
        ]);

        Friend::create([
            'owner_id' => $otherUser->getKey(),
            'owner_type' => get_class($otherUser),
            'party_id' => $myself->getKey(),
            'party_type' => get_class($myself),
        ]);

        $this->actingAs($myself);

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $otherUser->getKey(),
        ])
            ->assertSuccessful()
            ->assertJson([
                'type' => 1,
                'type_verbose' => 'PRIVATE',
                'pending' => false,
                'group' => false,
                'unread' => true,
                'name' => $otherUser->name(),
                'resources' => [
                    'latest_message' => [
                        'body' => 'Hello World!',
                    ],
                    'recipient' => [
                        'name' => $otherUser->name(),
                        'options' => [
                            'friend_status' => 1,
                            'friend_status_verbose' => 'FRIEND',
                        ],
                    ],
                ],
            ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $otherUser->getKey(),
            'pending' => false,
        ]);
    }

    /** @test */
    public function creating_new_private_forbidden_when_one_exist()
    {
        $this->doesntExpectEvents([
            NewThreadBroadcast::class,
            NewThreadEvent::class,
        ]);

        $users = UserModel::all();

        $this->actingAs($users->first());

        $this->postJson(route('api.messenger.privates.store'), [
            'message' => 'Hello World!',
            'recipient_alias' => 'user',
            'recipient_id' => $users->last()->getKey(),
        ])
            ->assertForbidden();
    }

    /** @test */
    public function recipient_can_approve_pending_thread()
    {
        $this->expectsEvents([
            ThreadApprovalBroadcast::class,
            ThreadApprovalEvent::class,
        ]);

        $myself = UserModel::first();

        $thread = Thread::create(Definitions::DefaultThread);

        $otherUser = UserModel::create([
            'name' => 'Jane Smith',
            'email' => 'smith@example.net',
            'password' => 'secret',
        ]);

        $thread->participants()->create(array_merge(Definitions::DefaultParticipant, [
            'owner_id' => $myself->getKey(),
            'owner_type' => get_class($myself),
            'pending' => true,
        ]));

        $thread->participants()->create(array_merge(Definitions::DefaultParticipant, [
            'owner_id' => $otherUser->getKey(),
            'owner_type' => get_class($otherUser),
            'pending' => false,
        ]));

        $this->actingAs($myself);

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $thread->id,
        ]), [
            'approve' => true,
        ])
            ->assertSuccessful();

        $this->assertDatabaseHas('participants', [
            'owner_id' => $myself->getKey(),
            'pending' => false,
        ]);
    }

    /** @test */
    public function recipient_can_deny_pending_thread()
    {
        $this->expectsEvents([
            ThreadApprovalBroadcast::class,
            ThreadApprovalEvent::class,
        ]);

        $myself = UserModel::first();

        $thread = Thread::create(Definitions::DefaultThread);

        $otherUser = UserModel::create([
            'name' => 'Jane Smith',
            'email' => 'smith@example.net',
            'password' => 'secret',
        ]);

        $thread->participants()->create(array_merge(Definitions::DefaultParticipant, [
            'owner_id' => $myself->getKey(),
            'owner_type' => get_class($myself),
            'pending' => true,
        ]));

        $thread->participants()->create(array_merge(Definitions::DefaultParticipant, [
            'owner_id' => $otherUser->getKey(),
            'owner_type' => get_class($otherUser),
            'pending' => false,
        ]));

        $this->actingAs($myself);

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $thread->id,
        ]), [
            'approve' => false,
        ])
            ->assertSuccessful();

        $this->assertSoftDeleted('threads', [
            'id' => $thread->id,
        ]);
    }
}
