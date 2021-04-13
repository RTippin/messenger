<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\LeaveThread;
use RTippin\Messenger\Broadcasting\ThreadLeftBroadcast;
use RTippin\Messenger\Events\ThreadLeftEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Listeners\ArchiveEmptyThread;
use RTippin\Messenger\Listeners\ThreadLeftMessage;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class LeaveThreadTest extends FeatureTestCase
{
    private Thread $group;
    private Participant $participant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->createGroupThread($this->tippin);
        $this->participant = $this->group->participants()->first();
        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_soft_deletes_participant()
    {
        app(LeaveThread::class)->withoutDispatches()->execute($this->group);

        $this->assertSoftDeleted('participants', [
            'id' => $this->participant->id,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        Event::fake([
            ThreadLeftBroadcast::class,
            ThreadLeftEvent::class,
        ]);

        app(LeaveThread::class)->execute($this->group);

        Event::assertDispatched(function (ThreadLeftBroadcast $event) {
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (ThreadLeftEvent $event) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($this->group->id, $event->thread->id);
            $this->assertEquals($this->participant->id, $event->participant->id);

            return true;
        });
    }

    /** @test */
    public function it_dispatches_listeners()
    {
        Bus::fake();

        app(LeaveThread::class)->withoutBroadcast()->execute($this->group);

        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === ArchiveEmptyThread::class;
        });
        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === ThreadLeftMessage::class;
        });
    }
}
