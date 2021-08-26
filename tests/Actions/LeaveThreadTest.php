<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\LeaveThread;
use RTippin\Messenger\Broadcasting\ThreadLeftBroadcast;
use RTippin\Messenger\Events\ThreadLeftEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\ThreadLeftMessage;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class LeaveThreadTest extends FeatureTestCase
{
    use BroadcastLogger;

    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_soft_deletes_participant()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();

        app(LeaveThread::class)->execute($thread);

        $this->assertSoftDeleted('participants', [
            'id' => $participant->id,
        ]);
        $this->assertSame(1, Participant::count());
    }

    /** @test */
    public function it_soft_deletes_participant_and_thread_when_last_participant()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();

        app(LeaveThread::class)->execute($thread);

        $this->assertSoftDeleted('participants', [
            'id' => $participant->id,
        ]);
        $this->assertSoftDeleted('threads', [
            'id' => $thread->id,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ThreadLeftBroadcast::class,
            ThreadLeftEvent::class,
        ]);
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();

        app(LeaveThread::class)->execute($thread);

        Event::assertDispatched(function (ThreadLeftBroadcast $event) use ($thread) {
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($thread->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (ThreadLeftEvent $event) use ($thread, $participant) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($thread->id, $event->thread->id);
            $this->assertEquals($participant->id, $event->participant->id);

            return true;
        });
        $this->logBroadcast(ThreadLeftBroadcast::class);
    }

    /** @test */
    public function it_doesnt_fire_events_if_last_participant()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ThreadLeftBroadcast::class,
            ThreadLeftEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);

        app(LeaveThread::class)->execute($thread);

        Event::assertNotDispatched(ThreadLeftBroadcast::class);
        Event::assertNotDispatched(ThreadLeftEvent::class);
    }

    /** @test */
    public function it_dispatches_subscriber_job()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        $thread = $this->createGroupThread($this->tippin, $this->doe);

        app(LeaveThread::class)->withoutBroadcast()->execute($thread);

        Bus::assertDispatched(ThreadLeftMessage::class);
    }

    /** @test */
    public function it_runs_subscriber_job_now()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('queued', false);
        $thread = $this->createGroupThread($this->tippin, $this->doe);

        app(LeaveThread::class)->withoutBroadcast()->execute($thread);

        Bus::assertDispatchedSync(ThreadLeftMessage::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_job_if_disabled()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('enabled', false);
        $thread = $this->createGroupThread($this->tippin, $this->doe);

        app(LeaveThread::class)->withoutBroadcast()->execute($thread);

        Bus::assertNotDispatched(ThreadLeftMessage::class);
    }
}
