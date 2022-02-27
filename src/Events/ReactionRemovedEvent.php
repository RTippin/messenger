<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;

class ReactionRemovedEvent
{
    use SerializesModels;

    /**
     * @param  MessengerProvider  $provider
     * @param  array  $reaction
     */
    public function __construct(
        public MessengerProvider $provider,
        public array $reaction
    ) {
    }
}
