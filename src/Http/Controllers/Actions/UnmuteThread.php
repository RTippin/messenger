<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Actions\Threads\UnmuteThread as UnmuteThreadAction;
use RTippin\Messenger\Models\Thread;

class UnmuteThread
{
    use AuthorizesRequests;

    /**
     * Un-Mute the thread.
     *
     * @param  UnmuteThreadAction  $unmuteThread
     * @param  Thread  $thread
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function __invoke(UnmuteThreadAction $unmuteThread, Thread $thread): JsonResponse
    {
        $this->authorize('mutes', $thread);

        return $unmuteThread->execute($thread)->getMessageResponse();
    }
}
