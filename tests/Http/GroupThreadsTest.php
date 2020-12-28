<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\NewThreadBroadcast;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Events\NewThreadEvent;
use RTippin\Messenger\Events\ParticipantsAddedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\UserModel;

class GroupThreadsTest extends FeatureTestCase
{
    /** @test */
    public function guest_is_unauthorized()
    {
        $this->getJson(route('api.messenger.groups.index'))
            ->assertUnauthorized();

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => 'Test Group',
        ])
            ->assertUnauthorized();
    }

    /** @test */
    public function user_has_one_group()
    {
        $myself = UserModel::first();

        $group = Thread::create([
            'type' => 2,
            'subject' => 'First Test Group',
            'image' => '5.png',
            'add_participants' => true,
            'invitations' => true,
        ]);

        $group->participants()
            ->create(array_merge(Definitions::DefaultAdminParticipant, [
                'owner_id' => $myself->getKey(),
                'owner_type' => get_class($myself),
            ]));

        $this->actingAs($myself);

        $this->getJson(route('api.messenger.groups.index'))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'id' => $group->id,
                        'type' => 2,
                        'type_verbose' => 'GROUP',
                        'group' => true,
                        'name' => 'First Test Group',
                        'options' => [
                            'admin' => true,
                        ],
                    ],
                ],
                'meta' => [
                    'final_page' => true,
                    'index' => true,
                    'per_page' => Messenger::getThreadsIndexCount(),
                    'results' => 1,
                    'total' => 1,
                ],
            ]);
    }

    /** @test */
    public function store_new_group_validates_request()
    {
        $this->actingAs(UserModel::firstOr());

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => null,
        ])
            ->assertJsonValidationErrors('subject');

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => '12',
            'providers' => [],
        ])
            ->assertJsonValidationErrors([
                'subject',
                'providers',
            ]);

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => 'Test Group',
            'providers' => [
                [
                    'id' => null,
                    'alias' => null,
                ],
                [
                    '404' => true,
                    'missing' => false,
                ],
            ],
        ])
            ->assertJsonValidationErrors([
                'providers.0.id',
                'providers.0.alias',
                'providers.1.id',
                'providers.1.alias',
            ]);
    }

    /** @test */
    public function store_group_without_extra_participants()
    {
        $this->expectsEvents([
            NewThreadEvent::class,
        ]);

        $this->doesntExpectEvents([
            NewThreadBroadcast::class,
        ]);

        $this->actingAs(UserModel::first());

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => 'Test Group',
        ])
            ->assertSuccessful()
            ->assertJson([
                'type' => 2,
                'type_verbose' => 'GROUP',
                'group' => true,
                'options' => [
                    'admin' => true,
                    'invitations' => true,
                    'add_participants' => true,
                ],
                'resources' => [
                    'latest_message' => [
                        'type' => 93,
                        'type_verbose' => 'GROUP_CREATED',
                        'body' => 'created Test Group',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('threads', [
            'subject' => 'Test Group',
        ]);
    }

    /** @test */
    public function store_group_with_extra_participants_will_ignore_participant_if_not_friend()
    {
        $this->expectsEvents([
            NewThreadEvent::class,
        ]);

        $this->doesntExpectEvents([
            NewThreadBroadcast::class,
            ParticipantsAddedEvent::class,
        ]);

        $users = UserModel::all();

        $this->actingAs($users->first());

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => 'Test Group',
            'providers' => [
                [
                    'id' => $users->last()->getKey(),
                    'alias' => 'user',
                ],
            ],
        ])
            ->assertSuccessful()
            ->assertJson([
                'type' => 2,
                'type_verbose' => 'GROUP',
                'group' => true,
                'options' => [
                    'admin' => true,
                    'invitations' => true,
                    'add_participants' => true,
                ],
                'resources' => [
                    'latest_message' => [
                        'type' => 93,
                        'type_verbose' => 'GROUP_CREATED',
                        'body' => 'created Test Group',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('threads', [
            'subject' => 'Test Group',
        ]);
    }

    /** @test */
    public function store_group_with_extra_participant_that_is_friend()
    {
        $this->expectsEvents([
            NewThreadBroadcast::class,
            NewThreadEvent::class,
            ParticipantsAddedEvent::class,
        ]);

        $users = UserModel::all();

        Friend::create([
            'owner_id' => $users->first()->getKey(),
            'owner_type' => get_class($users->first()),
            'party_id' => $users->last()->getKey(),
            'party_type' => get_class($users->last()),
        ]);

        Friend::create([
            'owner_id' => $users->last()->getKey(),
            'owner_type' => get_class($users->last()),
            'party_id' => $users->first()->getKey(),
            'party_type' => get_class($users->first()),
        ]);

        $this->actingAs($users->first());

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => 'Test Group',
            'providers' => [
                [
                    'id' => $users->last()->getKey(),
                    'alias' => 'user',
                ],
            ],
        ])
            ->assertSuccessful()
            ->assertJson([
                'type' => 2,
                'type_verbose' => 'GROUP',
                'group' => true,
                'options' => [
                    'admin' => true,
                    'invitations' => true,
                    'add_participants' => true,
                ],
                'resources' => [
                    'latest_message' => [
                        'type' => 93,
                        'type_verbose' => 'GROUP_CREATED',
                        'body' => 'created Test Group',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('threads', [
            'subject' => 'Test Group',
        ]);
    }
}
