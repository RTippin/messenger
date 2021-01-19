<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\NewThreadBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\ParticipantsAddedEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class AddParticipantsTest extends FeatureTestCase
{
    private Thread $group;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    private MessengerProvider $developers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->developers = $this->companyDevelopers();

        $this->group = $this->createGroupThread($this->tippin, $this->doe);

        $this->createFriends($this->tippin, $this->doe);

        $this->createFriends($this->tippin, $this->developers);
    }

    /** @test */
    public function forbidden_to_view_add_participants_on_private_thread()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.add.participants', [
            'thread' => $private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_participant_forbidden_to_view_add_participants()
    {
        $this->actingAs($this->developers);

        $this->getJson(route('api.messenger.threads.add.participants', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_admin_without_permission_forbidden_to_view_add_participants()
    {
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.add.participants', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_forbidden_to_view_add_participants_when_disabled_from_settings()
    {
        $this->group->update([
            'add_participants' => false,
        ]);

        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.add.participants', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_admin_with_permission_can_view_add_participants()
    {
        $this->group->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first()
            ->update([
                'add_participants' => true,
            ]);

        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.add.participants', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function admin_can_view_add_participants()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.add.participants', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'party' => [
                        'name' => 'Developers',
                    ],
                    'party_id' => $this->developers->getKey(),
                ],
            ]);
    }

    /** @test */
    public function admin_can_add_many_participants()
    {
        $laravel = $this->companyLaravel();

        $smith = $this->createJaneSmith();

        $this->expectsEvents([
            NewThreadBroadcast::class,
            ParticipantsAddedEvent::class,
        ]);

        $this->createFriends($this->tippin, $smith);

        $this->createFriends($this->tippin, $laravel);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.participants.store', [
            'thread' => $this->group->id,
        ]), [
            'providers' => [
                [
                    'id' => $smith->getKey(),
                    'alias' => 'user',
                ],
                [
                    'id' => $laravel->getKey(),
                    'alias' => 'company',
                ],
            ],
        ])
            ->assertSuccessful()
            ->assertJsonCount(2);
    }

    /** @test */
    public function non_admin_with_permission_can_add_participants()
    {
        $laravel = $this->companyLaravel();

        $this->expectsEvents([
            NewThreadBroadcast::class,
            ParticipantsAddedEvent::class,
        ]);

        $this->group->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first()
            ->update([
                'add_participants' => true,
            ]);

        $this->createFriends($this->doe, $laravel);

        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.participants.store', [
            'thread' => $this->group->id,
        ]), [
            'providers' => [
                [
                    'id' => $laravel->getKey(),
                    'alias' => 'company',
                ],
            ],
        ])
            ->assertSuccessful()
            ->assertJsonCount(1);
    }

    /** @test */
    public function non_friends_are_ignored_when_adding_participants()
    {
        $smith = $this->createJaneSmith();

        $laravel = $this->companyLaravel();

        $this->expectsEvents([
            NewThreadBroadcast::class,
            ParticipantsAddedEvent::class,
        ]);

        $this->createFriends($this->tippin, $smith);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.participants.store', [
            'thread' => $this->group->id,
        ]), [
            'providers' => [
                [
                    'id' => $smith->getKey(),
                    'alias' => 'user',
                ],
                [
                    'id' => $laravel->getKey(),
                    'alias' => 'company',
                ],
            ],
        ])
            ->assertSuccessful()
            ->assertJsonCount(1);
    }

    /** @test */
    public function no_participants_added_when_no_friends_found()
    {
        $smith = $this->createJaneSmith();

        $this->doesntExpectEvents([
            NewThreadBroadcast::class,
            ParticipantsAddedEvent::class,
        ]);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.participants.store', [
            'thread' => $this->group->id,
        ]), [
            'providers' => [
                [
                    'id' => $smith->getKey(),
                    'alias' => 'user',
                ],
            ],
        ])
            ->assertSuccessful()
            ->assertJsonCount(0);
    }

    /** @test */
    public function existing_participant_will_be_ignored_when_adding_participants()
    {
        $this->doesntExpectEvents([
            NewThreadBroadcast::class,
            ParticipantsAddedEvent::class,
        ]);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.participants.store', [
            'thread' => $this->group->id,
        ]), [
            'providers' => [
                [
                    'id' => $this->doe->getKey(),
                    'alias' => 'user',
                ],
            ],
        ])
            ->assertSuccessful()
            ->assertJsonCount(0);
    }

    /**
     * @test
     * @dataProvider providersValidation
     * @param $providers
     * @param $errors
     */
    public function add_participants_checks_providers($providers, $errors)
    {
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.participants.store', [
            'thread' => $this->group->id,
        ]), [
            'providers' => $providers,
        ])
            ->assertJsonValidationErrors($errors);
    }

    public function providersValidation(): array
    {
        return [
            'Alias and ID cannot be null' => [
                [['alias' => null, 'id' => null]],
                ['providers.0.alias', 'providers.0.id'],
            ],
            'Alias and ID cannot be empty' => [
                [['alias' => '', 'id' => '']],
                ['providers.0.alias', 'providers.0.id'],
            ],
            'Alias must be a string' => [
                [['alias' => 123, 'id' => 1]],
                ['providers.0.alias'],
            ],
            'Providers array cannot be empty' => [
                [[]],
                ['providers.0.alias', 'providers.0.id'],
            ],
            'Validates all items in the array' => [
                [['alias' => 'user', 'id' => 1], ['alias' => null, 'id' => null]],
                ['providers.1.alias', 'providers.1.id'],
            ],
        ];
    }
}
