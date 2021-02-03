<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\ArchiveThread;
use RTippin\Messenger\Broadcasting\ThreadArchivedBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\ThreadArchivedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Listeners\ThreadArchivedMessage;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class ArchiveThreadTest extends FeatureTestCase
{
    private Thread $private;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->private = $this->createPrivateThread($this->tippin, $this->doe);

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function archive_thread_soft_deletes_thread()
    {
        app(ArchiveThread::class)->withoutDispatches()->execute($this->private);

        $this->assertSoftDeleted('threads', [
            'id' => $this->private->id,
        ]);
    }

    /** @test */
    public function archive_thread_fires_events()
    {
        Event::fake([
            ThreadArchivedBroadcast::class,
            ThreadArchivedEvent::class,
        ]);

        app(ArchiveThread::class)->execute($this->private);

        Event::assertDispatched(function (ThreadArchivedBroadcast $event) {
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->private->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (ThreadArchivedEvent $event) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($this->private->id, $event->thread->id);

            return true;
        });
    }

    /** @test */
    public function archive_thread_triggers_listener()
    {
        Bus::fake();

        app(ArchiveThread::class)->withoutBroadcast()->execute($this->private);

        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === ThreadArchivedMessage::class;
        });
    }
}
