<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\ParticipantPermissionsBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\ParticipantPermissionsEvent;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class UpdateParticipantPermissionsTest extends FeatureTestCase
{
    private Thread $group;

    private Participant $participant;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->group = $this->createGroupThread($this->tippin, $this->doe);

        $this->participant = $this->group->participants()
            ->where('admin', '=', false)
            ->first();
    }

    /** @test */
    public function user_forbidden_to_update_private_participant_permissions()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $participant = $private->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first();

        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.participants.update', [
            'thread' => $private->id,
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
    public function update_group_participant_permissions_without_changes_fires_no_events()
    {
        $this->doesntExpectEvents([
            ParticipantPermissionsBroadcast::class,
            ParticipantPermissionsEvent::class,
        ]);

        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.participants.update', [
            'thread' => $this->group->id,
            'participant' => $this->participant->id,
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
    public function admin_can_update_group_participant_permissions()
    {
        $this->expectsEvents([
            ParticipantPermissionsBroadcast::class,
            ParticipantPermissionsEvent::class,
        ]);

        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.participants.update', [
            'thread' => $this->group->id,
            'participant' => $this->participant->id,
        ]), [
            'send_messages' => false,
            'add_participants' => true,
            'manage_invites' => true,
            'start_calls' => true,
            'send_knocks' => true,
        ])
            ->assertSuccessful()
            ->assertJson([
                'id' => $this->participant->id,
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
        $this->group->update([
            'lockout' => true,
        ]);

        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.participants.update', [
            'thread' => $this->group->id,
            'participant' => $this->participant->id,
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
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.participants.update', [
            'thread' => $this->group->id,
            'participant' => $this->participant->id,
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
