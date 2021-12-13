<?php

namespace RTippin\Messenger\Actions\Threads;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\DatabaseManager;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\ThreadLeftBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\ThreadLeftEvent;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;
use Throwable;

class LeaveThread extends BaseMessengerAction
{
    /**
     * @var BroadcastDriver
     */
    private BroadcastDriver $broadcaster;

    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var DatabaseManager
     */
    private DatabaseManager $database;

    /**
     * @var bool
     */
    private bool $threadArchived = false;

    /**
     * LeaveThread constructor.
     *
     * @param  Messenger  $messenger
     * @param  BroadcastDriver  $broadcaster
     * @param  Dispatcher  $dispatcher
     * @param  DatabaseManager  $database
     */
    public function __construct(Messenger $messenger,
                                BroadcastDriver $broadcaster,
                                Dispatcher $dispatcher,
                                DatabaseManager $database)
    {
        $this->broadcaster = $broadcaster;
        $this->dispatcher = $dispatcher;
        $this->messenger = $messenger;
        $this->database = $database;
    }

    /**
     * Leave the group thread. Archive the group if no one ie left.
     *
     * @param  Thread  $thread
     * @return $this
     *
     * @throws Throwable
     */
    public function execute(Thread $thread): self
    {
        $this->setThread($thread)->process();

        if (! $this->threadArchived) {
            $this->fireBroadcast()->fireEvents();
        }

        return $this;
    }

    /**
     * @throws Throwable
     */
    private function process(): void
    {
        $this->isChained()
            ? $this->handle()
            : $this->database->transaction(fn () => $this->handle());
    }

    /**
     * Archive the current participant. If no participants
     * are left, archive the thread.
     *
     * @return void
     */
    private function handle(): void
    {
        $this->getThread()->currentParticipant()->delete();

        if (! $this->getThread()->participants()->count()) {
            $this->getThread()->delete();
            $this->threadArchived = true;
        }
    }

    /**
     * @return $this
     */
    private function fireBroadcast(): self
    {
        if ($this->shouldFireBroadcast()) {
            $this->broadcaster
                ->to($this->getThread()->currentParticipant())
                ->with($this->generateBroadcastResource())
                ->broadcast(ThreadLeftBroadcast::class);
        }

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new ThreadLeftEvent(
                $this->messenger->getProvider(true),
                $this->getThread(true),
                $this->getThread()->currentParticipant()->withoutRelations()
            ));
        }
    }

    /**
     * @return array
     */
    private function generateBroadcastResource(): array
    {
        return [
            'thread_id' => $this->getThread()->id,
        ];
    }
}
