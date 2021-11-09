<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;

class BotActionHandledEvent
{
    use SerializesModels;

    /**
     * @var BotAction
     */
    public BotAction $action;

    /**
     * @var Message
     */
    public Message $message;

    /**
     * @var string|null
     */
    public ?string $trigger;

    /**
     * Create a new event instance.
     *
     * @param  BotAction  $action
     * @param  Message  $message
     * @param  string|null  $trigger
     */
    public function __construct(BotAction $action,
                                Message $message,
                                ?string $trigger)
    {
        $this->action = $action;
        $this->message = $message;
        $this->trigger = $trigger;
    }
}
