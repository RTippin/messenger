<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\RemoveParticipant;
use RTippin\Messenger\Broadcasting\ThreadLeftBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\RemovedFromThreadEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class RemoveParticipantTest extends FeatureTestCase
{
    private Thread $group;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->group = $this->createGroupThread($this->tippin, $this->doe);
    }

    /** @test */
    public function remove_participant_soft_deletes_participant()
    {
        $participant = $this->group->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first();

        app(RemoveParticipant::class)->withoutDispatches()->execute($this->group, $participant);

        $this->assertSoftDeleted('participants', [
            'id' => $participant->id,
        ]);
    }

    /** @test */
    public function remove_participant_fires_events()
    {
        Event::fake([
            ThreadLeftBroadcast::class,
            RemovedFromThreadEvent::class,
        ]);

        Messenger::setProvider($this->tippin);

        $participant = $this->group->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first();

        app(RemoveParticipant::class)->execute($this->group, $participant);

        Event::assertDispatched(function (ThreadLeftBroadcast $event) {
            $this->assertContains('private-user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (RemovedFromThreadEvent $event) use ($participant) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($this->group->id, $event->thread->id);
            $this->assertSame($participant->id, $event->participant->id);

            return true;
        });
    }
}
