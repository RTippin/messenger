<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;

class BotActionRemovedEvent
{
    use SerializesModels;

    /**
     * @param  MessengerProvider|null  $provider
     * @param  array  $action
     */
    public function __construct(
        public ?MessengerProvider $provider,
        public array $action
    ){}
}
