<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Http\Collections\FriendCollection;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\Friends\FriendRepository;

class FilterAddParticipants
{
    use AuthorizesRequests;

    /**
     * @param FriendRepository $friendRepository
     * @param Thread $thread
     * @return FriendCollection
     * @throws AuthorizationException
     */
    public function __invoke(FriendRepository $friendRepository, Thread $thread)
    {
        $this->authorize('addParticipants', $thread);

        return new FriendCollection(
            $friendRepository->getProviderFriendsNotInThread($thread)
        );
    }
}