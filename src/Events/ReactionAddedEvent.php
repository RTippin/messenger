<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\MessageReaction;

class ReactionAddedEvent
{
    use SerializesModels;

    /**
     * @param  MessageReaction  $reaction
     */
    public function __construct(
        public MessageReaction $reaction
    ){}
}
