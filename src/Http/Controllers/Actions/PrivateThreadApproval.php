<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Actions\Threads\ThreadApproval;
use RTippin\Messenger\Http\Request\ThreadApprovalRequest;
use RTippin\Messenger\Models\Thread;

class PrivateThreadApproval
{
    use AuthorizesRequests;

    /**
     * @param  ThreadApprovalRequest  $request
     * @param  ThreadApproval  $threadApproval
     * @param  Thread  $thread
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function __invoke(ThreadApprovalRequest $request,
                             ThreadApproval $threadApproval,
                             Thread $thread): JsonResponse
    {
        $this->authorize('approval', $thread);

        return $threadApproval->execute(
            $thread,
            $request->validated()['approve']
        )->getMessageResponse();
    }
}
