<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Actions\Calls\IgnoreCall as IgnoreCallAction;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use Throwable;

class IgnoreCall
{
    use AuthorizesRequests;

    /**
     * Leave the call.
     *
     * @param  IgnoreCallAction  $ignoreCall
     * @param  Thread  $thread
     * @param  Call  $call
     * @return JsonResponse
     *
     * @throws AuthorizationException|Throwable
     */
    public function __invoke(IgnoreCallAction $ignoreCall,
                             Thread $thread,
                             Call $call): JsonResponse
    {
        $this->authorize('ignore', [
            $call,
            $thread,
        ]);

        return $ignoreCall->execute($thread, $call)->getMessageResponse();
    }
}
