<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Actions\Threads\DemoteAdmin as DemoteAdminAction;
use RTippin\Messenger\Http\Resources\ParticipantResource;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;

class DemoteAdmin
{
    use AuthorizesRequests;

    /**
     * Demote participant from admin.
     *
     * @param  DemoteAdminAction  $demoteAdmin
     * @param  Thread  $thread
     * @param  Participant  $participant
     * @return ParticipantResource
     *
     * @throws AuthorizationException
     */
    public function __invoke(DemoteAdminAction $demoteAdmin,
                             Thread $thread,
                             Participant $participant): ParticipantResource
    {
        $this->authorize('demote', [
            $participant,
            $thread,
        ]);

        return $demoteAdmin->execute($thread, $participant)->getJsonResource();
    }
}
