<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;

class BotActionHandledEvent
{
    use SerializesModels;

    /**
     * @param  BotAction  $action
     * @param  Message  $message
     * @param  string|null  $trigger
     */
    public function __construct(
        public BotAction $action,
        public Message $message,
        public ?string $trigger
    ){}
}
