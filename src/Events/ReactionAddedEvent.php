<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\MessageReaction;

class ReactionAddedEvent
{
    use SerializesModels;

    /**
     * @var MessageReaction
     */
    public MessageReaction $reaction;

    /**
     * @var bool
     */
    public bool $isMessageOwner;

    /**
     * Create a new event instance.
     *
     * @param MessageReaction $reaction
     */
    public function __construct(MessageReaction $reaction)
    {
        $this->reaction = $reaction;
    }
}
