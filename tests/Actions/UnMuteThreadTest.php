<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\UnmuteThread;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\ParticipantUnMutedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class UnMuteThreadTest extends FeatureTestCase
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
    public function unmute_thread_updates_participant()
    {
        $participant = $this->group->participants()->first();

        $participant->update([
            'muted' => true,
        ]);

        app(UnmuteThread::class)->withoutDispatches()->execute($this->group);

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'muted' => false,
        ]);
    }

    /** @test */
    public function unmute_thread_fires_event()
    {
        Event::fake([
            ParticipantUnMutedEvent::class,
        ]);

        $participant = $this->group->participants()->first();

        $participant->update([
            'muted' => true,
        ]);

        app(UnmuteThread::class)->execute($this->group);

        Event::assertDispatched(function (ParticipantUnMutedEvent $event) use ($participant) {
            return $participant->id === $event->participant->id;
        });
    }
}
