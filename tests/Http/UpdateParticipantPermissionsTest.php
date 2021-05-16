<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class UpdateParticipantPermissionsTest extends HttpTestCase
{
    /** @test */
    public function user_forbidden_to_update_private_participant_permissions()
    {
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.participants.update', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]), [
            'send_messages' => false,
            'add_participants' => true,
            'manage_invites' => true,
            'start_calls' => true,
            'send_knocks' => true,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function update_permissions_without_changes_successful()
    {
        $thread = $this->createGroupThread($this->tippin);
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.participants.update', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]), [
            'send_messages' => true,
            'add_participants' => false,
            'manage_invites' => false,
            'start_calls' => false,
            'send_knocks' => false,
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function admin_can_update_permissions()
    {
        $thread = $this->createGroupThread($this->tippin);
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.participants.update', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]), [
            'send_messages' => false,
            'add_participants' => true,
            'manage_invites' => true,
            'start_calls' => true,
            'send_knocks' => true,
        ])
            ->assertSuccessful()
            ->assertJson([
                'id' => $participant->id,
                'send_messages' => false,
                'add_participants' => true,
                'manage_invites' => true,
                'start_calls' => true,
                'send_knocks' => true,
                'owner' => [
                    'name' => 'John Doe',
                ],
            ]);
    }

    /** @test */
    public function admin_forbidden_to_update_group_participant_permissions_when_thread_locked()
    {
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.participants.update', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]), [
            'send_messages' => false,
            'add_participants' => true,
            'manage_invites' => true,
            'start_calls' => true,
            'send_knocks' => true,
        ])
            ->assertForbidden();
    }

    /**
     * @test
     * @dataProvider permissionsValidation
     * @param $permissionValue
     */
    public function update_group_participant_checks_boolean_values($permissionValue)
    {
        $thread = $this->createGroupThread($this->tippin);
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.participants.update', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]), [
            'send_messages' => $permissionValue,
            'add_participants' => $permissionValue,
            'manage_invites' => $permissionValue,
            'start_calls' => $permissionValue,
            'send_knocks' => $permissionValue,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'send_messages',
                'add_participants',
                'manage_invites',
                'start_calls',
                'send_knocks',
            ]);
    }

    public function permissionsValidation(): array
    {
        return [
            'Values cannot be empty' => [''],
            'Values cannot be string' => ['string'],
            'Values cannot be integers' => [5],
            'Values cannot be null' => [null],
            'Values cannot be an array' => [[1, 2]],
        ];
    }
}
