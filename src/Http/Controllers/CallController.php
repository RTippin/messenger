<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Psr\SimpleCache\InvalidArgumentException;
use RTippin\Messenger\Actions\Calls\StoreCall;
use RTippin\Messenger\Http\Collections\CallCollection;
use RTippin\Messenger\Http\Resources\CallResource;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\CallRepository;
use Throwable;

class CallController
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     *
     * @param CallRepository $repository
     * @param Thread $thread
     * @return CallCollection
     * @throws AuthorizationException
     */
    public function index(CallRepository $repository, Thread $thread)
    {
        $this->authorize('viewAny', [
            Call::class,
            $thread,
        ]);

        return new CallCollection(
            $repository->getThreadCallsIndex($thread),
            $thread
        );
    }

    /**
     * Display call history pagination.
     *
     * @param CallRepository $repository
     * @param Thread $thread
     * @param Call $call
     * @return CallCollection
     * @throws AuthorizationException
     */
    public function paginate(CallRepository $repository,
                                        Thread $thread,
                                        Call $call)
    {
        $this->authorize('viewAny', [
            Call::class,
            $thread,
        ]);

        return new CallCollection(
            $repository->getThreadCallsPage($thread, $call),
            $thread,
            true,
            $call->id
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreCall $storeCall
     * @param Thread $thread
     * @return CallResource
     * @throws AuthorizationException|Throwable|InvalidArgumentException
     */
    public function store(StoreCall $storeCall, Thread $thread)
    {
        $this->authorize('create', [
            Call::class,
            $thread,
        ]);

        return $storeCall->execute($thread)
            ->getJsonResource();
    }

    /**
     * Display the specified resource.
     *
     * @param Thread $thread
     * @param Call $call
     * @return CallResource
     * @throws AuthorizationException
     */
    public function show(Thread $thread, Call $call)
    {
        $this->authorize('view', [
            $call,
            $thread,
        ]);

        return new CallResource(
            $call->load(['owner', 'participants.owner']),
            $thread,
            true
        );
    }
}
