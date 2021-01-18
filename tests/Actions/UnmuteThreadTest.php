<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\UnmuteThread;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\ParticipantUnMutedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class UnmuteThreadTest extends FeatureTestCase
{
    private Thread $group;

    private Participant $participant;

    private MessengerProvider $tippin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->group = $this->createGroupThread($this->tippin);

        $this->participant = $this->group->participants()->first();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function unmute_thread_updates_participant()
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
    public function unmute_thread_fires_event()
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
