<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\Bot;

class NewBotEvent
{
    use SerializesModels;

    /**
     * @param  Bot  $bot
     */
    public function __construct(
        public Bot $bot
    ) {
    }
}
