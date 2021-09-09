<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Actions\Calls\CallHeartbeat as Heartbeat;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;

class CallHeartbeat
{
    use AuthorizesRequests;

    /**
     * @param  Heartbeat  $heartbeat
     * @param  Thread  $thread
     * @param  Call  $call
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function __invoke(Heartbeat $heartbeat,
                             Thread $thread,
                             Call $call): JsonResponse
    {
        $this->authorize('heartbeat', [
            $call,
            $thread,
        ]);

        return $heartbeat->execute($call)->getMessageResponse();
    }
}
