<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\ArchiveThread;
use RTippin\Messenger\Broadcasting\ThreadArchivedBroadcast;
use RTippin\Messenger\Events\ThreadArchivedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\ThreadArchivedMessage;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class ArchiveThreadTest extends FeatureTestCase
{
    use BroadcastLogger;

    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_soft_deletes_thread()
    {
        $thread = Thread::factory()->create();

        app(ArchiveThread::class)->execute($thread);

        $this->assertSoftDeleted('threads', [
            'id' => $thread->id,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            ThreadArchivedBroadcast::class,
            ThreadArchivedEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);

        app(ArchiveThread::class)->execute($thread);

        Event::assertDispatched(function (ThreadArchivedBroadcast $event) use ($thread) {
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($thread->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (ThreadArchivedEvent $event) use ($thread) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($thread->id, $event->thread->id);

            return true;
        });
        $this->logBroadcast(ThreadArchivedBroadcast::class);
    }

    /** @test */
    public function it_dispatches_subscriber_job()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        $thread = $this->createGroupThread($this->tippin);

        app(ArchiveThread::class)->withoutBroadcast()->execute($thread);

        Bus::assertDispatched(ThreadArchivedMessage::class);
    }

    /** @test */
    public function it_runs_subscriber_job_now()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('queued', false);
        $thread = $this->createGroupThread($this->tippin);

        app(ArchiveThread::class)->withoutBroadcast()->execute($thread);

        Bus::assertDispatchedSync(ThreadArchivedMessage::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_job_if_disabled()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('enabled', false);
        $thread = $this->createGroupThread($this->tippin);

        app(ArchiveThread::class)->withoutBroadcast()->execute($thread);

        Bus::assertNotDispatched(ThreadArchivedMessage::class);
    }
}
