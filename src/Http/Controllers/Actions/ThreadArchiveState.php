<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Models\Thread;

class ThreadArchiveState
{
    use AuthorizesRequests;

    /**
     * @param  Thread  $thread
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function __invoke(Thread $thread): JsonResponse
    {
        $this->authorize('delete', $thread);

        return new JsonResponse([
            'name' => $thread->name(),
            'group' => $thread->isGroup(),
            'created_at' => $thread->created_at,
            'messages_count' => $thread->messages()->count(),
            'participants_count' => $thread->participants()->count(),
            'calls_count' => $thread->calls()->videoCall()->count(),
        ]);
    }
}
