<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Actions\Threads\MarkParticipantRead;
use RTippin\Messenger\Models\Thread;

class MarkThreadRead
{
    use AuthorizesRequests;

    /**
     * Mark thread read for current participant.
     *
     * @param  MarkParticipantRead  $markParticipantRead
     * @param  Thread  $thread
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function __invoke(MarkParticipantRead $markParticipantRead, Thread $thread): JsonResponse
    {
        $this->authorize('view', $thread);

        return $markParticipantRead->execute(
            $thread->currentParticipant(),
            $thread
        )->getMessageResponse();
    }
}
