<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Repositories\ThreadRepository;

class UnreadThreadsCount
{
    /**
     * How many unread threads does current provider have?
     *
     * @param  ThreadRepository  $repository
     * @return JsonResponse
     */
    public function __invoke(ThreadRepository $repository): JsonResponse
    {
        return new JsonResponse([
            'unread_threads_count' => $repository->getProviderUnreadThreadsBuilder()->count(),
        ], 200);
    }
}
