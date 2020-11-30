<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use RTippin\Messenger\Actions\Threads\UnmuteThread as UnmuteThreadAction;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Models\Thread;

class UnmuteThread
{
    use AuthorizesRequests;

    /**
     * Mute the thread to stop further notifications/updates
     *
     * @param UnmuteThreadAction $unmuteThread
     * @param Thread $thread
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function __invoke(UnmuteThreadAction $unmuteThread, Thread $thread)
    {
        $this->authorize('view', $thread);

        return $unmuteThread->execute($thread)
            ->getMessageResponse();
    }
}