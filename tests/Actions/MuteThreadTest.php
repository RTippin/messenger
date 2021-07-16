<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\MuteThread;
use RTippin\Messenger\Events\ParticipantMutedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class MuteThreadTest extends FeatureTestCase
{
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

        app(MuteThread::class)->execute($thread);

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'muted' => true,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ParticipantMutedEvent::class,
        ]);
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();

        app(MuteThread::class)->execute($thread);

        Event::assertDispatched(function (ParticipantMutedEvent $event) use ($participant) {
            return $participant->id === $event->participant->id;
        });
    }

    /** @test */
    public function it_doesnt_fire_events_if_already_muted()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ParticipantMutedEvent::class,
        ]);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->muted()->create();

        app(MuteThread::class)->execute($thread);

        Event::assertNotDispatched(ParticipantMutedEvent::class);
    }
}
