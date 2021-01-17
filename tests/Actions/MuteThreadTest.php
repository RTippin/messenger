<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\MuteThread;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\ParticipantMutedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class MuteThreadTest extends FeatureTestCase
{
    private Thread $group;

    private MessengerProvider $tippin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->group = $this->createGroupThread($this->tippin);

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function mute_thread_updates_participant()
    {
        $participant = $this->group->participants()->first();

        app(MuteThread::class)->withoutDispatches()->execute($this->group);

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'muted' => true,
        ]);
    }

    /** @test */
    public function mute_thread_fires_event()
    {
        Event::fake([
            ParticipantMutedEvent::class,
        ]);

        $participant = $this->group->participants()->first();

        app(MuteThread::class)->execute($this->group);

        Event::assertDispatched(function (ParticipantMutedEvent $event) use ($participant) {
            return $participant->id === $event->participant->id;
        });
    }
}
