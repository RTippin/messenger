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
     * Display a listing of the resource.
     *
     * @param PrivateThreadRepository $repository
     * @return PrivateThreadCollection
     * @throws AuthorizationException
     */
    public function index(PrivateThreadRepository $repository)
    {
        $this->authorize('viewAny', Thread::class);

        return new PrivateThreadCollection(
            $repository->getProviderPrivateThreadsIndex()
        );
    }

    /**
     * Display private threads history pagination
     *
     * @param PrivateThreadRepository $repository
     * @param Thread $private
     * @return PrivateThreadCollection
     * @throws AuthorizationException
     */
    public function paginate(PrivateThreadRepository $repository, Thread $private)
    {
        $this->authorize('privateMethod', $private);

        return new PrivateThreadCollection(
            $repository->getProviderPrivateThreadsPage($private),
            true,
            $private->id
        );
    }


    /**
     * @param PrivateThreadRequest $request
     * @param StorePrivateThread $storePrivateThread
     * @return ThreadResource
     * @throws AuthorizationException
     * @throws Throwable
     */
    public function store(PrivateThreadRequest $request,
                          StorePrivateThread $storePrivateThread)
    {
        $this->authorize('create', Thread::class);

        return $storePrivateThread->execute(
            $request->validated()
        )->getJsonResource();
    }
}
