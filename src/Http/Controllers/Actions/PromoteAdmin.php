<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Actions\Threads\PromoteAdmin as PromoteAdminAction;
use RTippin\Messenger\Http\Resources\ParticipantResource;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;

class PromoteAdmin
{
    use AuthorizesRequests;

    /**
     * Promote admin privilege from participant.
     *
     * @param PromoteAdminAction $promoteAdmin
     * @param Thread $thread
     * @param Participant $participant
     * @return ParticipantResource
     * @throws AuthorizationException
     */
    public function __invoke(PromoteAdminAction $promoteAdmin,
                             Thread $thread,
                             Participant $participant)
    {
        $this->authorize('promote', [
            $participant,
            $thread,
        ]);

        return $promoteAdmin->execute($thread, $participant)
            ->getJsonResource();
    }
}
