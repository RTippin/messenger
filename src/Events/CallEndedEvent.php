<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Call;

class CallEndedEvent
{
    use SerializesModels;

    /**
     * @param  MessengerProvider|null  $provider
     * @param  Call  $call
     */
    public function __construct(
        public ?MessengerProvider $provider,
        public Call $call
    ){}
}
