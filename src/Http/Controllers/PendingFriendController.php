<?php

namespace RTippin\Messenger\Http\Controllers;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Actions\Friends\AcceptFriendRequest;
use RTippin\Messenger\Actions\Friends\DenyFriendRequest;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Http\Collections\PendingFriendCollection;
use RTippin\Messenger\Http\Resources\FriendResource;
use RTippin\Messenger\Http\Resources\PendingFriendResource;
use RTippin\Messenger\Http\Resources\ProviderResource;
use RTippin\Messenger\Models\PendingFriend;
use Throwable;

class PendingFriendController
{
    use AuthorizesRequests;

    /**
     * Display a listing of the providers pending friends.
     *
     * @param  FriendDriver  $repository
     * @return PendingFriendCollection
     *
     * @throws AuthorizationException
     */
    public function index(FriendDriver $repository): PendingFriendCollection
    {
        $this->authorize('viewAny', PendingFriend::class);

        return new PendingFriendCollection(
            $repository->getProviderPendingFriends(true)
        );
    }

    /**
     * Display the pending friend.
     *
     * @param  PendingFriend  $pending
     * @return PendingFriendResource
     *
     * @throws AuthorizationException
     */
    public function show(PendingFriend $pending): PendingFriendResource
    {
        $this->authorize('view', $pending);

        return new PendingFriendResource($pending);
    }

    /**
     * Accept the pending friend request.
     *
     * @param  AcceptFriendRequest  $acceptFriendRequest
     * @param  PendingFriend  $pending
     * @return FriendResource
     *
     * @throws Throwable|AuthorizationException
     */
    public function update(AcceptFriendRequest $acceptFriendRequest, PendingFriend $pending): FriendResource
    {
        $this->authorize('update', $pending);

        return $acceptFriendRequest->execute($pending)
            ->getJsonResource();
    }

    /**
     * Deny the pending friend request.
     *
     * @param  DenyFriendRequest  $denyFriendRequest
     * @param  PendingFriend  $pending
     * @return ProviderResource
     *
     * @throws Exception|AuthorizationException
     */
    public function destroy(DenyFriendRequest $denyFriendRequest, PendingFriend $pending): ProviderResource
    {
        $this->authorize('delete', $pending);

        return $denyFriendRequest->execute($pending)->getJsonResource();
    }
}
