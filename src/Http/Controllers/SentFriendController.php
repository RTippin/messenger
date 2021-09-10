<?php

namespace RTippin\Messenger\Http\Controllers;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Actions\Friends\CancelFriendRequest;
use RTippin\Messenger\Actions\Friends\StoreFriendRequest;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Exceptions\FriendException;
use RTippin\Messenger\Http\Collections\SentFriendCollection;
use RTippin\Messenger\Http\Request\FriendRequest;
use RTippin\Messenger\Http\Resources\ProviderResource;
use RTippin\Messenger\Http\Resources\SentFriendResource;
use RTippin\Messenger\Models\SentFriend;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SentFriendController
{
    use AuthorizesRequests;

    /**
     * Display a listing of the providers sent friend request.
     *
     * @param  FriendDriver  $repository
     * @return SentFriendCollection
     *
     * @throws AuthorizationException
     */
    public function index(FriendDriver $repository): SentFriendCollection
    {
        $this->authorize('viewAny', SentFriend::class);

        return new SentFriendCollection(
            $repository->getProviderSentFriends(true)
        );
    }

    /**
     * Store a new friend request.
     *
     * @param  FriendRequest  $request
     * @param  StoreFriendRequest  $storeFriendRequest
     * @return SentFriendResource
     *
     * @throws AuthorizationException|NotFoundHttpException|FriendException
     */
    public function store(FriendRequest $request, StoreFriendRequest $storeFriendRequest): SentFriendResource
    {
        $this->authorize('create', SentFriend::class);

        return $storeFriendRequest->execute(
            $request->validated()
        )->getJsonResource();
    }

    /**
     * Display the sent friend request.
     *
     * @param  SentFriend  $sent
     * @return SentFriendResource
     *
     * @throws AuthorizationException
     */
    public function show(SentFriend $sent): SentFriendResource
    {
        $this->authorize('view', $sent);

        return new SentFriendResource($sent);
    }

    /**
     * Cancel the sent friend request.
     *
     * @param  CancelFriendRequest  $cancelFriendRequest
     * @param  SentFriend  $sent
     * @return ProviderResource
     *
     * @throws Exception|AuthorizationException
     */
    public function destroy(CancelFriendRequest $cancelFriendRequest, SentFriend $sent): ProviderResource
    {
        $this->authorize('delete', $sent);

        return $cancelFriendRequest->execute($sent)->getJsonResource();
    }
}
