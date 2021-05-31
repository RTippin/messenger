<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\RemoveParticipant;
use RTippin\Messenger\Broadcasting\ThreadLeftBroadcast;
use RTippin\Messenger\Events\RemovedFromThreadEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\RemovedFromThreadMessage;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class RemoveParticipantTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_soft_deletes_participant()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();

        app(RemoveParticipant::class)->execute($thread, $participant);

        $this->assertSoftDeleted('participants', [
            'id' => $participant->id,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ThreadLeftBroadcast::class,
            RemovedFromThreadEvent::class,
        ]);
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();

        app(RemoveParticipant::class)->execute($thread, $participant);

        Event::assertDispatched(function (ThreadLeftBroadcast $event) use ($thread) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($thread->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (RemovedFromThreadEvent $event) use ($thread, $participant) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($thread->id, $event->thread->id);
            $this->assertSame($participant->id, $event->participant->id);

            return true;
        });
    }

    /** @test */
    public function it_dispatches_subscriber_job()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();

        app(RemoveParticipant::class)->execute($thread, $participant);

        Bus::assertDispatched(RemovedFromThreadMessage::class);
    }

    /** @test */
    public function it_runs_subscriber_job_now()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('queued', false);
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();

        app(RemoveParticipant::class)->execute($thread, $participant);

        Bus::assertDispatchedSync(RemovedFromThreadMessage::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_job_if_disabled()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('enabled', false);
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();

        app(RemoveParticipant::class)->execute($thread, $participant);

        Bus::assertNotDispatched(RemovedFromThreadMessage::class);
    }
}
