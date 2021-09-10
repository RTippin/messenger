<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Http\Collections\FriendCollection;
use RTippin\Messenger\Models\Thread;

class FilterAddParticipants
{
    use AuthorizesRequests;

    /**
     * @param  FriendDriver  $repository
     * @param  Thread  $thread
     * @return FriendCollection
     *
     * @throws AuthorizationException
     */
    public function __invoke(FriendDriver $repository, Thread $thread): FriendCollection
    {
        $this->authorize('addParticipants', $thread);

        return new FriendCollection(
            $repository->getProviderFriendsNotInThread($thread)
        );
    }
}
