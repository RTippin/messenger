<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Invite;

class InviteArchivedEvent
{
    use SerializesModels;

    /**
     * @param  MessengerProvider|null  $provider
     * @param  Invite  $invite
     */
    public function __construct(
        public ?MessengerProvider $provider,
        public Invite $invite
    ) {
    }
}
