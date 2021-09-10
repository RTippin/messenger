<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Actions\Threads\MuteThread as MuteThreadAction;
use RTippin\Messenger\Models\Thread;

class MuteThread
{
    use AuthorizesRequests;

    /**
     * Mute the thread to stop further notifications/updates.
     *
     * @param  MuteThreadAction  $muteThread
     * @param  Thread  $thread
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function __invoke(MuteThreadAction $muteThread, Thread $thread): JsonResponse
    {
        $this->authorize('mutes', $thread);

        return $muteThread->execute($thread)->getMessageResponse();
    }
}
