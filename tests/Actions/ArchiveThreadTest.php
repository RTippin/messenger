<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\ArchiveThread;
use RTippin\Messenger\Broadcasting\ThreadArchivedBroadcast;
use RTippin\Messenger\Events\ThreadArchivedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class ArchiveThreadTest extends FeatureTestCase
{
    private Thread $private;

    protected function setUp(): void
    {
        parent::setUp();

        $this->private = $this->createPrivateThread(
            $this->userTippin(),
            $this->userDoe()
        );
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
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        Messenger::setProvider($tippin);

        Event::fake([
            ThreadArchivedBroadcast::class,
            ThreadArchivedEvent::class,
        ]);

        app(ArchiveThread::class)->execute($this->private);

        Event::assertDispatched(function (ThreadArchivedBroadcast $event) use ($tippin, $doe) {
            $this->assertContains('private-user.'.$tippin->getKey(), $event->broadcastOn());
            $this->assertContains('private-user.'.$doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->private->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (ThreadArchivedEvent $event) use ($tippin) {
            $this->assertSame($tippin->getKey(), $event->provider->getKey());
            $this->assertSame($this->private->id, $event->thread->id);

            return true;
        });
    }
}
