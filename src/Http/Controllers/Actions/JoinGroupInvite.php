<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Actions\Invites\JoinWithInvite;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Participant;
use Throwable;

class JoinGroupInvite
{
    use AuthorizesRequests;

    /**
     * @param  JoinWithInvite  $joinWithInvite
     * @param  Invite  $invite
     * @return Participant
     *
     * @throws AuthorizationException|Exception
     * @throws Throwable|FeatureDisabledException
     */
    public function __invoke(JoinWithInvite $joinWithInvite, Invite $invite): Participant
    {
        $this->authorize('join', $invite);

        return $joinWithInvite->execute($invite)->getParticipant();
    }
}
