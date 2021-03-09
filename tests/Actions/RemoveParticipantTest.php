<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\RemoveParticipant;
use RTippin\Messenger\Broadcasting\ThreadLeftBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\RemovedFromThreadEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Listeners\RemovedFromThreadMessage;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class RemoveParticipantTest extends FeatureTestCase
{
    private Thread $group;
    private Participant $participant;
    private MessengerProvider $tippin;
    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->doe = $this->userDoe();
        $this->group = $this->createGroupThread($this->tippin, $this->doe);
        $this->participant = $this->group->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first();
        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_soft_deletes_participant()
    {
        app(RemoveParticipant::class)->withoutDispatches()->execute(
            $this->group,
            $this->participant
        );

        $this->assertSoftDeleted('participants', [
            'id' => $this->participant->id,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        Event::fake([
            ThreadLeftBroadcast::class,
            RemovedFromThreadEvent::class,
        ]);

        app(RemoveParticipant::class)->execute(
            $this->group,
            $this->participant
        );

        Event::assertDispatched(function (ThreadLeftBroadcast $event) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (RemovedFromThreadEvent $event) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($this->group->id, $event->thread->id);
            $this->assertSame($this->participant->id, $event->participant->id);

            return true;
        });
    }

    /** @test */
    public function it_dispatches_listeners()
    {
        Bus::fake();

        app(RemoveParticipant::class)->withoutBroadcast()->execute(
            $this->group,
            $this->participant
        );

        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === RemovedFromThreadMessage::class;
        });
    }
}
