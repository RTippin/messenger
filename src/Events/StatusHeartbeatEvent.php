<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;

class StatusHeartbeatEvent
{
    use SerializesModels;

    /**
     * @var MessengerProvider
     */
    public MessengerProvider $provider;

    /**
     * @var string
     */
    public string $IP;

    /**
     * @var bool
     */
    public bool $away;

    /**
     * Create a new event instance.
     *
     * @param  MessengerProvider  $provider
     * @param  bool  $away
     * @param  string  $IP
     */
    public function __construct(MessengerProvider $provider,
                                bool $away,
                                string $IP)
    {
        $this->provider = $provider;
        $this->IP = $IP;
        $this->away = $away;
    }
}
