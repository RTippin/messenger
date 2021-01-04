<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\ParticipantPermissionsBroadcast;
use RTippin\Messenger\Events\ParticipantPermissionsEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class ParticipantsTest extends FeatureTestCase
{
    private Thread $private;

    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $this->group = $this->createGroupThread(
            $tippin,
            $doe,
            $this->companyDevelopers()
        );

        $this->private = $this->createPrivateThread(
            $tippin,
            $doe
        );
    }

    /** @test */
    public function guest_is_unauthorized_to_view_participants()
    {
        $this->getJson(route('api.messenger.threads.participants.index', [
            'thread' => $this->group->id,
        ]))
            ->assertUnauthorized();
    }

    /** @test */
    public function non_participant_forbidden_to_view_participants()
    {
        $this->actingAs($this->createJaneSmith());

        $this->getJson(route('api.messenger.threads.participants.index', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_can_view_group_participants()
    {
        $this->actingAs($this->userDoe());

        $this->getJson(route('api.messenger.threads.participants.index', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function user_can_view_private_participants()
    {
        $this->actingAs($this->userDoe());

        $this->getJson(route('api.messenger.threads.participants.index', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function user_can_view_private_participant()
    {
        $doe = $this->userDoe();

        $participant = $this->private->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first();

        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.threads.participants.show', [
            'thread' => $this->private->id,
            'participant' => $participant->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $participant->id,
                'owner' => [
                    'name' => 'John Doe',
                ],
            ]);
    }

    /** @test */
    public function user_can_view_group_participant()
    {
        $developers = $this->companyDevelopers();

        $participant = $this->group->participants()
            ->where('owner_id', '=', $developers->getKey())
            ->where('owner_type', '=', get_class($developers))
            ->first();

        $this->actingAs($this->userDoe());

        $this->getJson(route('api.messenger.threads.participants.show', [
            'thread' => $this->group->id,
            'participant' => $participant->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $participant->id,
                'owner' => [
                    'name' => 'Developers',
                ],
            ]);
    }

    /** @test */
    public function user_forbidden_to_update_private_participant_permissions()
    {
        $doe = $this->userDoe();

        $participant = $this->private->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first();

        $this->actingAs($this->userTippin());

        $this->putJson(route('api.messenger.threads.participants.update', [
            'thread' => $this->private->id,
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
}
