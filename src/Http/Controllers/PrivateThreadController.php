<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Actions\Threads\StorePrivateThread;
use RTippin\Messenger\Http\Collections\PrivateThreadCollection;
use RTippin\Messenger\Http\Request\PrivateThreadRequest;
use RTippin\Messenger\Http\Resources\ThreadResource;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\PrivateThreadRepository;
use Throwable;

class PrivateThreadController
{
    use AuthorizesRequests;

    /**
     * Display a listing of the most recent private threads.
     *
     * @param  PrivateThreadRepository  $repository
     * @return PrivateThreadCollection
     *
     * @throws AuthorizationException
     */
    public function index(PrivateThreadRepository $repository): PrivateThreadCollection
    {
        $this->authorize('viewAny', Thread::class);

        return new PrivateThreadCollection(
            $repository->getProviderPrivateThreadsIndex()
        );
    }

    /**
     * Display private threads history pagination.
     *
     * @param  PrivateThreadRepository  $repository
     * @param  Thread  $private
     * @return PrivateThreadCollection
     *
     * @throws AuthorizationException
     */
    public function paginate(PrivateThreadRepository $repository, Thread $private): PrivateThreadCollection
    {
        $this->authorize('privateMethod', $private);

        return new PrivateThreadCollection(
            $repository->getProviderPrivateThreadsPage($private),
            true,
            $private->id
        );
    }

    /**
     * Store a new private thread.
     *
     * @param  PrivateThreadRequest  $request
     * @param  StorePrivateThread  $storePrivateThread
     * @return ThreadResource
     *
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function store(PrivateThreadRequest $request, StorePrivateThread $storePrivateThread): ThreadResource
    {
        $this->authorize('create', Thread::class);

        return $storePrivateThread->execute(
            $request->validated()
        )->getJsonResource();
    }
}
