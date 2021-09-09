<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Invite;

class InviteArchivedEvent
{
    use SerializesModels;

    /**
     * @var null|MessengerProvider
     */
    public ?MessengerProvider $provider;

    /**
     * @var Invite
     */
    public Invite $invite;

    /**
     * Create a new event instance.
     *
     * @param  MessengerProvider|null  $provider
     * @param  Invite  $invite
     */
    public function __construct(?MessengerProvider $provider, Invite $invite)
    {
        $this->invite = $invite;
        $this->provider = $provider;
    }
}
