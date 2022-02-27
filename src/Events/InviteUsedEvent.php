<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Thread;

class InviteUsedEvent
{
    use SerializesModels;

    /**
     * @param  MessengerProvider  $provider
     * @param  Thread  $thread
     * @param  Invite  $invite
     */
    public function __construct(
        public MessengerProvider $provider,
        public Thread $thread,
        public Invite $invite
    ) {
    }
}
