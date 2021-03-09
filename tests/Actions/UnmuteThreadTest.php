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
        $this->participant->update([
            'muted' => true,
        ]);

        app(UnmuteThread::class)->withoutDispatches()->execute($this->group);

        $this->assertDatabaseHas('participants', [
            'id' => $this->participant->id,
            'muted' => false,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        Event::fake([
            ParticipantUnMutedEvent::class,
        ]);
        $this->participant->update([
            'muted' => true,
        ]);

        app(UnmuteThread::class)->execute($this->group);

        Event::assertDispatched(function (ParticipantUnMutedEvent $event) {
            return $this->participant->id === $event->participant->id;
        });
    }
}
