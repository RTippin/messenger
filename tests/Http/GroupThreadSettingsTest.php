<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class GroupThreadSettingsTest extends HttpTestCase
{
    /** @test */
    public function admin_can_view_group_settings()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.settings', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function admin_forbidden_to_view_group_settings_when_thread_locked()
    {
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.settings', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_admin_forbidden_to_view_group_settings()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.settings', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function update_group_settings_without_changes_successful()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.settings', [
            'thread' => $thread->id,
        ]), [
            'subject' => 'First Test Group',
            'add_participants' => true,
            'invitations' => true,
            'calling' => true,
            'messaging' => true,
            'knocks' => true,
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function admin_can_update_group_settings()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.settings', [
            'thread' => $thread->id,
        ]), [
            'subject' => 'New',
            'add_participants' => false,
            'invitations' => false,
            'calling' => false,
            'messaging' => false,
            'knocks' => false,
        ])
            ->assertSuccessful()
            ->assertJson([
                'name' => 'New',
                'add_participants' => false,
                'invitations' => false,
                'calling' => false,
                'messaging' => false,
                'knocks' => false,
            ]);
    }

    /** @test */
    public function forbidden_to_update_group_settings_when_thread_locked()
    {
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.settings', [
            'thread' => $thread->id,
        ]), [
            'subject' => 'First Test Group',
            'add_participants' => true,
            'invitations' => true,
            'calling' => true,
            'messaging' => false,
            'knocks' => false,
        ])
            ->assertForbidden();
    }

    /**
     * @test
     * @dataProvider settingsValidation
     * @param $fieldValue
     */
    public function group_settings_checks_booleans($fieldValue)
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.settings', [
            'thread' => $thread->id,
        ]), [
            'subject' => 'Passing',
            'messaging' => $fieldValue,
            'add_participants' => $fieldValue,
            'invitations' => $fieldValue,
            'calling' => $fieldValue,
            'knocks' => $fieldValue,
        ])
            ->assertStatus(422)
            ->assertJsonMissingValidationErrors('subject')
            ->assertJsonValidationErrors([
                'add_participants',
                'invitations',
                'calling',
                'messaging',
                'knocks',
            ]);
    }

    /**
     * @test
     * @dataProvider subjectValidation
     * @param $subject
     */
    public function group_settings_checks_subject($subject)
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.settings', [
            'thread' => $thread->id,
        ]), [
            'subject' => $subject,
            'messaging' => true,
            'add_participants' => true,
            'invitations' => true,
            'calling' => true,
            'knocks' => true,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('subject');
    }

    public function settingsValidation(): array
    {
        return [
            'Value cannot be an INT' => [2],
            'Value cannot be a string' => ['string'],
            'Value cannot be an array' => [[1, 2]],
            'Value cannot be null' => [null],
        ];
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
}
