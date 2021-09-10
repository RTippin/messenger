<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Models\Thread;

class IsThreadUnread
{
    use AuthorizesRequests;

    /**
     * Is the thread unread for current participant?
     *
     * @param  Thread  $thread
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function __invoke(Thread $thread): JsonResponse
    {
        $this->authorize('view', $thread);

        return new JsonResponse([
            'unread' => $thread->isUnread(),
        ], 200);
    }
}
