<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\ArchiveThread;
use RTippin\Messenger\Broadcasting\ThreadArchivedBroadcast;
use RTippin\Messenger\Events\ThreadArchivedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Listeners\ThreadArchivedMessage;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class ArchiveThreadTest extends FeatureTestCase
{
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
    }

    /** @test */
    public function it_dispatched_listeners()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        $thread = $this->createGroupThread($this->tippin);

        app(ArchiveThread::class)->withoutBroadcast()->execute($thread);

        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === ThreadArchivedMessage::class;
        });
    }
}
