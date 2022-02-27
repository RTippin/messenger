<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\BotAction;

class NewBotActionEvent
{
    use SerializesModels;

    /**
     * @param  BotAction  $botAction
     */
    public function __construct(
        public BotAction $botAction
    ) {
    }
}
