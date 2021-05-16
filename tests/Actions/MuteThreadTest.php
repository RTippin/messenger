<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\MuteThread;
use RTippin\Messenger\Events\ParticipantMutedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class MuteThreadTest extends FeatureTestCase
{
    /** @test */
    public function it_updates_participant()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();
        Messenger::setProvider($this->tippin);

        app(MuteThread::class)->withoutDispatches()->execute($thread);

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'muted' => true,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();
        Messenger::setProvider($this->tippin);
        Event::fake([
            ParticipantMutedEvent::class,
        ]);

        app(MuteThread::class)->execute($thread);

        Event::assertDispatched(function (ParticipantMutedEvent $event) use ($participant) {
            return $participant->id === $event->participant->id;
        });
    }

    /** @test */
    public function it_doesnt_fire_events_if_already_muted()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->muted()->create();
        Messenger::setProvider($this->tippin);
        Event::fake([
            ParticipantMutedEvent::class,
        ]);

        app(MuteThread::class)->execute($thread);

        Event::assertNotDispatched(ParticipantMutedEvent::class);
    }
}
