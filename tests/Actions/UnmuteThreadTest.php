<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\UnmuteThread;
use RTippin\Messenger\Events\ParticipantUnMutedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class UnmuteThreadTest extends FeatureTestCase
{
    /** @test */
    public function it_updates_participant()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->muted()->create();
        Messenger::setProvider($this->tippin);

        app(UnmuteThread::class)->withoutDispatches()->execute($thread);

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'muted' => false,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->muted()->create();
        Messenger::setProvider($this->tippin);
        Event::fake([
            ParticipantUnMutedEvent::class,
        ]);

        app(UnmuteThread::class)->execute($thread);

        Event::assertDispatched(function (ParticipantUnMutedEvent $event) use ($participant) {
            return $participant->id === $event->participant->id;
        });
    }

    /** @test */
    public function it_doesnt_fire_events_if_already_un_muted()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Messenger::setProvider($this->tippin);
        Event::fake([
            ParticipantUnMutedEvent::class,
        ]);

        app(UnmuteThread::class)->execute($thread);

        Event::assertNotDispatched(ParticipantUnMutedEvent::class);
    }
}
