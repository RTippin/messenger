<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;

class ReactionRemovedEvent
{
    use SerializesModels;

    /**
     * @var MessengerProvider
     */
    public MessengerProvider $provider;

    /**
     * @var array
     */
    public array $reaction;

    /**
     * Create a new event instance.
     *
     * @param  MessengerProvider  $provider
     * @param  array  $reaction
     */
    public function __construct(MessengerProvider $provider, array $reaction)
    {
        $this->provider = $provider;
        $this->reaction = $reaction;
    }
}
