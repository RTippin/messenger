<?php

namespace RTippin\Messenger\Http\Controllers;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Actions\Friends\CancelFriendRequest;
use RTippin\Messenger\Actions\Friends\StoreFriendRequest;
use RTippin\Messenger\Http\Collections\SentFriendCollection;
use RTippin\Messenger\Http\Request\FriendRequest;
use RTippin\Messenger\Http\Resources\ProviderResource;
use RTippin\Messenger\Http\Resources\SentFriendResource;
use RTippin\Messenger\Models\SentFriend;
use RTippin\Messenger\Repositories\Friends\SentFriendRepository;

class SentFriendController
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     *
     * @param SentFriendRepository $repository
     * @return SentFriendCollection
     * @throws AuthorizationException
     */
    public function index(SentFriendRepository $repository)
    {
        $this->authorize('viewAny', SentFriend::class);

        return new SentFriendCollection(
            $repository->getProviderSentFriends()
        );
    }

    /**
     * Store a newly created resource in storage.
     * @param FriendRequest $request
     * @param StoreFriendRequest $storeFriendRequest
     * @return SentFriendResource
     * @throws AuthorizationException|ModelNotFoundException
     */
    public function store(FriendRequest $request,
                          StoreFriendRequest $storeFriendRequest)
    {
        $this->authorize('create', SentFriend::class);

        return $storeFriendRequest->execute(
            $request->validated()
        )->getJsonResource();
    }

    /**
     * Display the specified resource.
     *
     * @param SentFriend $sent
     * @return SentFriendResource
     * @throws AuthorizationException
     */
    public function show(SentFriend $sent)
    {
        $this->authorize('view', $sent);

        return new SentFriendResource($sent);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param CancelFriendRequest $cancelFriendRequest
     * @param SentFriend $sent
     * @return ProviderResource
     * @throws Exception|AuthorizationException
     */
    public function destroy(CancelFriendRequest $cancelFriendRequest,
                            SentFriend $sent)
    {
        $this->authorize('delete', $sent);

        return $cancelFriendRequest->execute($sent)
            ->getJsonResource();
    }
}
