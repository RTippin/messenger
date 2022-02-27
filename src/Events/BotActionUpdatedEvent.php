<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\BotAction;

class BotActionUpdatedEvent
{
    use SerializesModels;

    /**
     * @param  MessengerProvider  $provider
     * @param  BotAction  $action
     */
    public function __construct(
        public MessengerProvider $provider,
        public BotAction $action
    ) {
    }
}
