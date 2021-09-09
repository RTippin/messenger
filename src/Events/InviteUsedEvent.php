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
     * @var Invite
     */
    public Invite $invite;

    /**
     * @var MessengerProvider
     */
    public MessengerProvider $provider;

    /**
     * @var Thread
     */
    public Thread $thread;

    /**
     * Create a new event instance.
     *
     * @param  MessengerProvider  $provider
     * @param  Thread  $thread
     * @param  Invite  $invite
     */
    public function __construct(MessengerProvider $provider,
                                Thread $thread,
                                Invite $invite)
    {
        $this->invite = $invite;
        $this->provider = $provider;
        $this->thread = $thread;
    }
}
