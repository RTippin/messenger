<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Tests\HttpTestCase;

class GroupThreadsTest extends HttpTestCase
{
    /** @test */
    public function user_has_one_group()
    {
        $this->logCurrentRequest();
        $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.groups.index'))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function user_can_view_paginated_groups()
    {
        $this->logCurrentRequest();
        $this->createGroupThread($this->tippin);
        $this->createGroupThread($this->tippin);
        $thread = $this->createGroupThread($this->tippin);
        $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.groups.page', [
            'group' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function store_group_without_extra_participants()
    {
        $this->logCurrentRequest();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => 'Test Group',
        ])
            ->assertSuccessful()
            ->assertJson([
                'type' => 2,
                'type_verbose' => 'GROUP',
                'group' => true,
                'name' => 'Test Group',
            ]);
    }

    /** @test */
    public function store_group_with_extra_participants_will_ignore_participant_if_not_friend()
    {
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => 'Test Group',
            'providers' => [
                [
                    'id' => $this->doe->getKey(),
                    'alias' => 'user',
                ],
            ],
        ])
            ->assertSuccessful();

        $this->assertDatabaseCount('participants', 1);
    }

    /** @test */
    public function store_group_with_one_added_participant()
    {
        $this->createFriends($this->tippin, $this->doe);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => 'Test Group Participants',
            'providers' => [
                [
                    'id' => $this->doe->getKey(),
                    'alias' => 'user',
                ],
            ],
        ])
            ->assertSuccessful();

        $this->assertDatabaseCount('participants', 2);
    }

    /** @test */
    public function store_group_with_multiple_providers()
    {
        $this->logCurrentRequest('WITH_PARTICIPANTS');
        $this->createFriends($this->tippin, $this->doe);
        $this->createFriends($this->tippin, $this->developers);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => 'Test Many Participants',
            'providers' => [
                [
                    'id' => $this->doe->getKey(),
                    'alias' => 'user',
                ],
                [
                    'id' => $this->developers->getKey(),
                    'alias' => 'company',
                ],
            ],
        ])
            ->assertSuccessful();

        $this->assertDatabaseCount('participants', 3);
    }

    /**
     * @test
     *
     * @dataProvider subjectFailsValidation
     *
     * @param  $subject
     */
    public function store_new_group_fails_validating_subject($subject)
    {
        $this->logCurrentRequest();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => $subject,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('subject');
    }

    /**
     * @test
     *
     * @dataProvider providersFailValidation
     *
     * @param  $providers
     * @param  $errors
     */
    public function store_new_group_fails_validating_providers($providers, $errors)
    {
        $this->logCurrentRequest();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => 'Passes',
            'providers' => $providers,
        ])
            ->assertStatus(422)
            ->assertJsonMissingValidationErrors('subject')
            ->assertJsonValidationErrors($errors);
    }

    public static function subjectFailsValidation(): array
    {
        return [
            'Subject cannot be an INT' => [2],
            'Subject cannot be single character' => ['x'],
            'Subject cannot be greater than 255 characters' => [str_repeat('X', 256)],
            'Subject cannot be an array' => [[1, 2]],
            'Subject cannot be null' => [null],
            'Subject cannot be empty' => [''],
            'Subject cannot be a file' => [UploadedFile::fake()->image('picture.png')],
        ];
    }

    public static function providersFailValidation(): array
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
