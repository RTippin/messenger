<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Call;

class CallIgnoredEvent
{
    use SerializesModels;

    /**
     * @param  Call  $call
     * @param  MessengerProvider  $provider
     */
    public function __construct(
        public Call $call,
        public MessengerProvider $provider
    ) {
    }
}
