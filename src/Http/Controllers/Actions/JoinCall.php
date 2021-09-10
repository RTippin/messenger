<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Actions\Calls\JoinCall as JoinCallAction;
use RTippin\Messenger\Http\Resources\CallParticipantResource;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;

class JoinCall
{
    use AuthorizesRequests;

    /**
     * Store or restore a call participant / join call.
     *
     * @param  JoinCallAction  $joinCall
     * @param  Thread  $thread
     * @param  Call  $call
     * @return CallParticipantResource
     *
     * @throws AuthorizationException
     */
    public function __invoke(JoinCallAction $joinCall,
                             Thread $thread,
                             Call $call): CallParticipantResource
    {
        $this->authorize('join', [
            $call,
            $thread,
        ]);

        return $joinCall->execute($call)->getJsonResource();
    }
}
