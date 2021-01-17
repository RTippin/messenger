<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\MarkParticipantRead;
use RTippin\Messenger\Broadcasting\ParticipantReadBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\ParticipantsReadEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class MarkParticipantReadTest extends FeatureTestCase
{
    private Thread $private;

    private MessengerProvider $tippin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->private = $this->createPrivateThread($this->tippin, $this->userDoe());
    }

    /** @test */
    public function mark_participant_read_updates_participant()
    {
        $participant = $this->private->participants()
            ->where('owner_id', '=', $this->tippin->getKey())
            ->where('owner_type', '=', get_class($this->tippin))
            ->first();

        $this->assertNull($participant->last_read);

        app(MarkParticipantRead::class)->withoutDispatches()->execute($participant, $this->private);

        $this->assertNotNull($participant->last_read);
    }

    /** @test */
    public function mark_participant_read_fires_events()
    {
        Event::fake([
            ParticipantReadBroadcast::class,
            ParticipantsReadEvent::class,
        ]);

        $participant = $this->private->participants()
            ->where('owner_id', '=', $this->tippin->getKey())
            ->where('owner_type', '=', get_class($this->tippin))
            ->first();

        app(MarkParticipantRead::class)->execute($participant, $this->private);

        Event::assertDispatched(function (ParticipantReadBroadcast $event) {
            $this->assertContains('private-user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($this->private->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (ParticipantsReadEvent $event) use ($participant) {
            return $participant->id === $event->participant->id;
        });
    }

    /** @test */
    public function mark_participant_read_fires_no_events_when_participant_not_updated()
    {
        $this->doesntExpectEvents([
            ParticipantReadBroadcast::class,
            ParticipantsReadEvent::class,
        ]);

        $participant = $this->private->participants()
            ->where('owner_id', '=', $this->tippin->getKey())
            ->where('owner_type', '=', get_class($this->tippin))
            ->first();

        $participant->update([
            'last_read' => now(),
        ]);

        $this->travel(5)->minutes();

        app(MarkParticipantRead::class)->execute($participant, $this->private);
    }

    /** @test */
    public function mark_participant_read_does_not_update_pending_participant()
    {
        $participant = $this->private->participants()
            ->where('owner_id', '=', $this->tippin->getKey())
            ->where('owner_type', '=', get_class($this->tippin))
            ->first();

        $participant->update([
            'pending' => true,
        ]);

        app(MarkParticipantRead::class)->execute($participant, $this->private);

        $this->assertNull($participant->last_read);
    }

    /** @test */
    public function mark_participant_read_updates_participant_when_no_thread_supplied()
    {
        $participant = $this->private->participants()
            ->where('owner_id', '=', $this->tippin->getKey())
            ->where('owner_type', '=', get_class($this->tippin))
            ->first();

        app(MarkParticipantRead::class)->withoutDispatches()->execute($participant);

        $this->assertNotNull($participant->last_read);
    }
}
