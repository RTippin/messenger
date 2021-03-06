<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\MarkParticipantRead;
use RTippin\Messenger\Broadcasting\ParticipantReadBroadcast;
use RTippin\Messenger\Events\ParticipantReadEvent;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class MarkParticipantReadTest extends FeatureTestCase
{
    /** @test */
    public function it_updates_participant()
    {
        $thread = Thread::factory()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();
        $read = now()->addMinutes(5)->format('Y-m-d H:i:s.u');
        Carbon::setTestNow($read);

        app(MarkParticipantRead::class)->execute($participant, $thread);

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'last_read' => $read,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ParticipantReadBroadcast::class,
            ParticipantReadEvent::class,
        ]);
        $thread = Thread::factory()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();

        app(MarkParticipantRead::class)->execute($participant, $thread);

        Event::assertDispatched(function (ParticipantReadBroadcast $event) use ($thread) {
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($thread->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (ParticipantReadEvent $event) use ($participant) {
            return $participant->id === $event->participant->id;
        });
    }

    /** @test */
    public function it_fires_no_events_if_participant_already_up_to_date()
    {
        BaseMessengerAction::enableEvents();
        $thread = Thread::factory()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->read()->create();

        $this->doesntExpectEvents([
            ParticipantReadBroadcast::class,
            ParticipantReadEvent::class,
        ]);

        app(MarkParticipantRead::class)->execute($participant, $thread);
    }

    /** @test */
    public function it_does_not_update_a_pending_participant()
    {
        $thread = Thread::factory()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->pending()->create();

        app(MarkParticipantRead::class)->execute($participant, $thread);

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'last_read' => null,
        ]);
    }

    /** @test */
    public function it_updates_participant_if_no_thread_supplied()
    {
        $participant = Participant::factory()->for(Thread::factory()->create())->owner($this->tippin)->create();
        $read = now()->addMinutes(5)->format('Y-m-d H:i:s.u');
        Carbon::setTestNow($read);

        app(MarkParticipantRead::class)->execute($participant);

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'last_read' => $read,
        ]);
    }
}
