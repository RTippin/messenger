<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use RTippin\Messenger\Actions\Invites\JoinWithInvite;
use RTippin\Messenger\Models\Invite;
use Throwable;

class JoinGroupInvite
{
    use AuthorizesRequests;

    /**
     * @param JoinWithInvite $joinWithInvite
     * @param Invite $invite
     * @return array
     * @throws AuthorizationException|Exception|Throwable
     */
    public function __invoke(JoinWithInvite $joinWithInvite, Invite $invite)
    {
        $this->authorize('join', $invite);

        return $joinWithInvite->execute($invite)
            ->getData();
    }
}
