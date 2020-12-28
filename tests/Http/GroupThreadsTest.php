<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\NewThreadBroadcast;
use RTippin\Messenger\Events\NewThreadEvent;
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
}
