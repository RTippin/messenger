<?php

namespace RTippin\Messenger\Http\Controllers;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Actions\Friends\AcceptFriendRequest;
use RTippin\Messenger\Actions\Friends\DenyFriendRequest;
use RTippin\Messenger\Http\Collections\PendingFriendCollection;
use RTippin\Messenger\Http\Resources\FriendResource;
use RTippin\Messenger\Http\Resources\PendingFriendResource;
use RTippin\Messenger\Http\Resources\ProviderResource;
use RTippin\Messenger\Models\PendingFriend;
use RTippin\Messenger\Repositories\Friends\PendingFriendRepository;
use Throwable;

class PendingFriendController
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     *
     * @param PendingFriendRepository $repository
     * @return PendingFriendCollection
     * @throws AuthorizationException
     */
    public function index(PendingFriendRepository $repository)
    {
        $this->authorize('viewAny', PendingFriend::class);

        return new PendingFriendCollection(
            $repository->getProviderPendingFriends()
        );
    }

    /**
     * Display the specified resource.
     *
     * @param PendingFriend $pending
     * @return PendingFriendResource
     * @throws AuthorizationException
     */
    public function show(PendingFriend $pending)
    {
        $this->authorize('view', $pending);

        return new PendingFriendResource($pending);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param AcceptFriendRequest $acceptFriendRequest
     * @param PendingFriend $pending
     * @return FriendResource
     * @throws Throwable|AuthorizationException
     */
    public function update(AcceptFriendRequest $acceptFriendRequest,
                           PendingFriend $pending)
    {
        $this->authorize('update', $pending);

        return $acceptFriendRequest->execute($pending)
            ->getJsonResource();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param DenyFriendRequest $denyFriendRequest
     * @param PendingFriend $pending
     * @return ProviderResource
     * @throws Exception|AuthorizationException
     */
    public function destroy(DenyFriendRequest $denyFriendRequest,
                            PendingFriend $pending)
    {
        $this->authorize('delete', $pending);

        return $denyFriendRequest->execute($pending)
            ->getJsonResource();
    }
}
