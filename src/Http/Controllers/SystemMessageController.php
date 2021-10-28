<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Http\Collections\SystemMessageCollection;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\SystemMessageRepository;

class SystemMessageController
{
    use AuthorizesRequests;

    /**
     * Display a listing of the most recent system messages.
     *
     * @param  SystemMessageRepository  $repository
     * @param  Thread  $thread
     * @return SystemMessageCollection
     *
     * @throws AuthorizationException
     */
    public function index(SystemMessageRepository $repository, Thread $thread): SystemMessageCollection
    {
        $this->authorize('viewAny', [
            Message::class,
            $thread,
        ]);

        return new SystemMessageCollection(
            $repository->getThreadSystemMessagesIndex($thread),
            $thread
        );
    }

    /**
     * Display system message history pagination.
     *
     * @param  SystemMessageRepository  $repository
     * @param  Thread  $thread
     * @param  Message  $log
     * @return SystemMessageCollection
     *
     * @throws AuthorizationException
     */
    public function paginate(SystemMessageRepository $repository,
                             Thread $thread,
                             Message $log): SystemMessageCollection
    {
        $this->authorize('view', [
            $log,
            $thread,
        ]);

        return new SystemMessageCollection(
            $repository->getThreadSystemMessagesPage($thread, $log),
            $thread,
            true,
            $log->id
        );
    }
}
