<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\ParticipantPermissionsBroadcast;
use RTippin\Messenger\Events\ParticipantPermissionsEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class UpdateParticipantPermissionsTest extends FeatureTestCase
{
    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->createGroupThread(
            $this->userTippin(),
            $this->userDoe(),
            $this->companyDevelopers()
        );
    }

    /** @test */
    public function user_forbidden_to_update_private_participant_permissions()
    {
        $doe = $this->userDoe();

        $tippin = $this->userTippin();

        $private = $this->createPrivateThread(
            $tippin,
            $doe
        );

        $participant = $private->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first();

        $this->actingAs($tippin);

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
        $doe = $this->userDoe();

        $this->doesntExpectEvents([
            ParticipantPermissionsBroadcast::class,
            ParticipantPermissionsEvent::class,
        ]);

        $participant = $this->group->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first();

        $this->actingAs($this->userTippin());

        $this->putJson(route('api.messenger.threads.participants.update', [
            'thread' => $this->group->id,
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
    public function admin_can_update_group_participant_permissions()
    {
        $doe = $this->userDoe();

        $tippin = $this->userTippin();

        Event::fake([
            ParticipantPermissionsBroadcast::class,
            ParticipantPermissionsEvent::class,
        ]);

        $participant = $this->group->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first();

        $this->actingAs($tippin);

        $this->putJson(route('api.messenger.threads.participants.update', [
            'thread' => $this->group->id,
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

        Event::assertDispatched(function (ParticipantPermissionsBroadcast $event) use ($doe) {
            $this->assertContains('private-user.'.$doe->getKey(), $event->broadcastOn());
            $this->assertEquals($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (ParticipantPermissionsEvent $event) use ($tippin, $participant) {
            $this->assertEquals($tippin->getKey(), $event->provider->getKey());
            $this->assertEquals($this->group->id, $event->thread->id);
            $this->assertEquals($participant->id, $event->participant->id);

            return true;
        });
    }

    /**
     * @test
     * @dataProvider permissionsValidation
     * @param $permissionValue
     */
    public function update_group_participant_checks_boolean_values($permissionValue)
    {
        $doe = $this->userDoe();

        $participant = $this->group->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first();

        $this->actingAs($this->userTippin());

        $this->putJson(route('api.messenger.threads.participants.update', [
            'thread' => $this->group->id,
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
