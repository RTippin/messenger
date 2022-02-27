<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\Invite;

class NewInviteEvent
{
    use SerializesModels;

    /**
     * @param  Invite  $invite
     */
    public function __construct(
        public Invite $invite
    ){}
}
