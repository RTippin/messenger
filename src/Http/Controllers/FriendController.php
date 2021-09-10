<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Actions\Friends\RemoveFriend;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Http\Collections\FriendCollection;
use RTippin\Messenger\Http\Resources\FriendResource;
use RTippin\Messenger\Http\Resources\ProviderResource;
use RTippin\Messenger\Models\Friend;
use Throwable;

class FriendController
{
    use AuthorizesRequests;

    /**
     * Display a listing of the providers friends.
     *
     * @param  FriendDriver  $repository
     * @return FriendCollection
     *
     * @throws AuthorizationException
     */
    public function index(FriendDriver $repository): FriendCollection
    {
        $this->authorize('viewAny', Friend::class);

        return new FriendCollection(
            $repository->getProviderFriends(true)
        );
    }

    /**
     * Display the friend.
     *
     * @param  Friend  $friend
     * @return FriendResource
     *
     * @throws AuthorizationException
     */
    public function show(Friend $friend): FriendResource
    {
        $this->authorize('view', $friend);

        return new FriendResource($friend);
    }

    /**
     * Remove the friend.
     *
     * @param  RemoveFriend  $removeFriend
     * @param  Friend  $friend
     * @return ProviderResource
     *
     * @throws Throwable|AuthorizationException
     *
     * @todo Return success response, not json resource.
     */
    public function destroy(RemoveFriend $removeFriend, Friend $friend): ProviderResource
    {
        $this->authorize('delete', $friend);

        return $removeFriend->execute($friend)->getJsonResource();
    }
}
