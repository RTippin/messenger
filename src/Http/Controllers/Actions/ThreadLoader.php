<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Actions\Threads\MarkParticipantRead;
use RTippin\Messenger\Http\Resources\ThreadResource;
use RTippin\Messenger\Models\Thread;

class ThreadLoader
{
    use AuthorizesRequests;

    /**
     * Eager load relations on thread.
     */
    const LOAD = [
        'latestMessage.owner',
        'participants.owner',
        'activeCall.participants.owner',
    ];

    /**
     * Display the specified thread with loaded relations for messages
     * and participants. We will also mark the thread as read.
     *
     * @param  MarkParticipantRead  $read
     * @param  Thread  $thread
     * @return ThreadResource
     *
     * @throws AuthorizationException
     */
    public function __invoke(MarkParticipantRead $read, Thread $thread): ThreadResource
    {
        $this->authorize('view', $thread);

        $read->execute(
            $thread->currentParticipant(),
            $thread
        );

        return new ThreadResource(
            $thread->load(self::LOAD),
            true,
            true
        );
    }
}
