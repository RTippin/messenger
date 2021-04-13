<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\UpdateParticipantPermissions;
use RTippin\Messenger\Broadcasting\ParticipantPermissionsBroadcast;
use RTippin\Messenger\Events\ParticipantPermissionsEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class UpdateParticipantPermissionsTest extends FeatureTestCase
{
    private Thread $group;
    private Participant $participant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->createGroupThread($this->tippin, $this->doe);
        $this->participant = $this->group->participants()->forProvider($this->doe)->first();
        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_updates_participant()
    {
        app(UpdateParticipantPermissions::class)->withoutDispatches()->execute(
            $this->group,
            $this->participant,
            [
                'send_messages' => false,
                'add_participants' => true,
                'manage_invites' => true,
                'start_calls' => true,
                'send_knocks' => true,
            ]
        );

        $this->assertDatabaseHas('participants', [
            'id' => $this->participant->id,
            'send_messages' => false,
            'add_participants' => true,
            'manage_invites' => true,
            'start_calls' => true,
            'send_knocks' => true,
        ]);
    }

    /** @test */
    public function it_fires_events_when_participant_updated()
    {
        Event::fake([
            ParticipantPermissionsBroadcast::class,
            ParticipantPermissionsEvent::class,
        ]);

        app(UpdateParticipantPermissions::class)->execute(
            $this->group,
            $this->participant,
            [
                'send_messages' => false,
                'add_participants' => true,
                'manage_invites' => true,
                'start_calls' => true,
                'send_knocks' => true,
            ]
        );

        Event::assertDispatched(function (ParticipantPermissionsBroadcast $event) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (ParticipantPermissionsEvent $event) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($this->group->id, $event->thread->id);
            $this->assertSame($this->participant->id, $event->participant->id);

            return true;
        });
    }

    /** @test */
    public function it_doesnt_fire_events_if_participant_not_changed()
    {
        $this->doesntExpectEvents([
            ParticipantPermissionsBroadcast::class,
            ParticipantPermissionsEvent::class,
        ]);

        app(UpdateParticipantPermissions::class)->execute(
            $this->group,
            $this->participant,
            [
                'send_messages' => true,
                'add_participants' => false,
                'manage_invites' => false,
                'start_calls' => false,
                'send_knocks' => false,
            ]
        );
    }
}
