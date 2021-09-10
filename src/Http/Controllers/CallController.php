<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Actions\Calls\StoreCall;
use RTippin\Messenger\Exceptions\NewCallException;
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
     * Display a listing of the most recent calls.
     *
     * @param  CallRepository  $repository
     * @param  Thread  $thread
     * @return CallCollection
     *
     * @throws AuthorizationException
     */
    public function index(CallRepository $repository, Thread $thread): CallCollection
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
     * @param  CallRepository  $repository
     * @param  Thread  $thread
     * @param  Call  $call
     * @return CallCollection
     *
     * @throws AuthorizationException
     */
    public function paginate(CallRepository $repository,
                             Thread $thread,
                             Call $call): CallCollection
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
     * Start a new call.
     *
     * @param  StoreCall  $storeCall
     * @param  Thread  $thread
     * @return CallResource
     *
     * @throws AuthorizationException|Throwable|NewCallException
     */
    public function store(StoreCall $storeCall, Thread $thread): CallResource
    {
        $this->authorize('create', [
            Call::class,
            $thread,
        ]);

        return $storeCall->execute($thread)->getJsonResource();
    }

    /**
     * Display the call.
     *
     * @param  Thread  $thread
     * @param  Call  $call
     * @return CallResource
     *
     * @throws AuthorizationException
     */
    public function show(Thread $thread, Call $call): CallResource
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
