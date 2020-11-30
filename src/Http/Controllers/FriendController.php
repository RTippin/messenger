<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Actions\Friends\RemoveFriend;
use RTippin\Messenger\Http\Collections\FriendCollection;
use RTippin\Messenger\Http\Resources\FriendResource;
use RTippin\Messenger\Http\Resources\ProviderResource;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Repositories\Friends\FriendRepository;
use Throwable;

class FriendController
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     *
     * @param FriendRepository $repository
     * @return FriendCollection|JsonResponse
     * @throws AuthorizationException
     */
    public function index(FriendRepository $repository)
    {
        $this->authorize('viewAny', Friend::class);

        return new FriendCollection(
            $repository->getProviderFriends()
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Friend $friend
     * @return FriendResource
     * @throws AuthorizationException
     */
    public function show(Friend $friend)
    {
        $this->authorize('view', $friend);

        return new FriendResource($friend);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param RemoveFriend $removeFriend
     * @param Friend $friend
     * @return ProviderResource
     * @throws Throwable|AuthorizationException
     */
    public function destroy(RemoveFriend $removeFriend,
                            Friend $friend)
    {
        $this->authorize('delete', $friend);

        return $removeFriend->execute($friend)
            ->getJsonResource();
    }
}
