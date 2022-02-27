<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Bot;

class BotArchivedEvent
{
    use SerializesModels;

    /**
     * @param  MessengerProvider  $provider
     * @param  Bot  $bot
     */
    public function __construct(
        public MessengerProvider $provider,
        public Bot $bot
    ) {
    }
}
