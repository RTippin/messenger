<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;

class StatusHeartbeatEvent
{
    use SerializesModels;

    /**
     * @param  MessengerProvider  $provider
     * @param  bool  $away
     * @param  string  $IP
     */
    public function __construct(
        public MessengerProvider $provider,
        public bool $away,
        public string $IP
    ){}
}
