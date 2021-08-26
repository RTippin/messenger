<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\UpdateParticipantPermissions;
use RTippin\Messenger\Broadcasting\ParticipantPermissionsBroadcast;
use RTippin\Messenger\Events\ParticipantPermissionsEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class UpdateParticipantPermissionsTest extends FeatureTestCase
{
    use BroadcastLogger;

    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_updates_participant()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();

        app(UpdateParticipantPermissions::class)->execute($thread, $participant, [
            'send_messages' => false,
            'add_participants' => true,
            'manage_invites' => true,
            'manage_bots' => true,
            'start_calls' => true,
            'send_knocks' => true,
        ]);

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'send_messages' => false,
            'add_participants' => true,
            'manage_invites' => true,
            'manage_bots' => true,
            'start_calls' => true,
            'send_knocks' => true,
        ]);
    }

    /** @test */
    public function it_fires_events_when_participant_updated()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ParticipantPermissionsBroadcast::class,
            ParticipantPermissionsEvent::class,
        ]);
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();

        app(UpdateParticipantPermissions::class)->execute($thread, $participant, [
            'send_messages' => false,
            'add_participants' => true,
            'manage_invites' => true,
            'manage_bots' => true,
            'start_calls' => true,
            'send_knocks' => true,
        ]);

        Event::assertDispatched(function (ParticipantPermissionsBroadcast $event) use ($thread) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($thread->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (ParticipantPermissionsEvent $event) use ($thread, $participant) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($thread->id, $event->thread->id);
            $this->assertSame($participant->id, $event->participant->id);

            return true;
        });
        $this->logBroadcast(ParticipantPermissionsBroadcast::class);
    }

    /** @test */
    public function it_doesnt_fire_events_if_participant_not_changed()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ParticipantPermissionsBroadcast::class,
            ParticipantPermissionsEvent::class,
        ]);
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();

        app(UpdateParticipantPermissions::class)->execute($thread, $participant, [
            'send_messages' => true,
            'add_participants' => false,
            'manage_invites' => false,
            'manage_bots' => false,
            'start_calls' => false,
            'send_knocks' => false,
        ]);

        Event::assertNotDispatched(ParticipantPermissionsBroadcast::class);
        Event::assertNotDispatched(ParticipantPermissionsEvent::class);
    }
}
