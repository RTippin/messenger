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
    private Thread $group;

    private Participant $participant;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();
        $this->group = $this->createGroupThread($tippin);
        $this->participant = $this->group->participants()->first();
        Messenger::setProvider($tippin);
    }

    /** @test */
    public function it_updates_participant()
    {
        app(MuteThread::class)->withoutDispatches()->execute($this->group);

        $this->assertDatabaseHas('participants', [
            'id' => $this->participant->id,
            'muted' => true,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        Event::fake([
            ParticipantMutedEvent::class,
        ]);

        app(MuteThread::class)->execute($this->group);

        Event::assertDispatched(function (ParticipantMutedEvent $event) {
            return $this->participant->id === $event->participant->id;
        });
    }
}
