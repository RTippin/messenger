<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Tests\FeatureTestCase;

class GroupThreadsTest extends FeatureTestCase
{
    /** @test */
    public function user_has_one_group()
    {
        $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.groups.index'))
            ->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function store_group_without_extra_participants()
    {
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
     * @dataProvider subjectValidation
     * @param $subject
     */
    public function store_new_group_checks_subject($subject)
    {
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => $subject,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('subject');
    }

    /**
     * @test
     * @dataProvider providersValidation
     * @param $providers
     * @param $errors
     */
    public function store_new_group_checks_providers($providers, $errors)
    {
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.groups.store'), [
            'subject' => 'Passes',
            'providers' => $providers,
        ])
            ->assertStatus(422)
            ->assertJsonMissingValidationErrors('subject')
            ->assertJsonValidationErrors($errors);
    }

    public function subjectValidation(): array
    {
        return [
            'Value cannot be an INT' => [2],
            'Value cannot be single character' => ['1'],
            'Value must be larger than 2 characters' => ['12'],
            'Value cannot be an array' => [[1, 2]],
            'Value cannot be null' => [null],
            'Value cannot be empty' => [''],
        ];
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
