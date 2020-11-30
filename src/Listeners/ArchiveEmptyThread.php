<?php

namespace RTippin\Messenger\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use RTippin\Messenger\Actions\Threads\ArchiveThread;
use RTippin\Messenger\Events\ThreadLeftEvent;
use RTippin\Messenger\Messenger;
use Throwable;

class ArchiveEmptyThread implements ShouldQueue
{
    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public $queue = 'messenger';

    /**
     * @var ArchiveThread
     */
    private ArchiveThread $archiveThread;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * Create the event listener.
     *
     * @param Messenger $messenger
     * @param ArchiveThread $archiveThread
     */
    public function __construct(Messenger $messenger,
                                ArchiveThread $archiveThread)
    {
        $this->archiveThread = $archiveThread;
        $this->messenger = $messenger;
    }

    /**
     * Handle the event.
     *
     * @param ThreadLeftEvent $event
     * @return void
     * @throws Throwable
     */
    public function handle(ThreadLeftEvent $event): void
    {
        $this->messenger->setProvider($event->provider);

        $this->archiveThread->withoutDispatches()->execute($event->thread);
    }

    /**
     * Determine whether the listener should be queued.
     *
     * @param ThreadLeftEvent $event
     * @return bool
     */
    public function shouldQueue(ThreadLeftEvent $event)
    {
        return ! $event->thread->participants()->count();
    }
}
