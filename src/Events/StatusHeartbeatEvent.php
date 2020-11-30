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
    public MessengerProvider $model;

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
     * @param MessengerProvider $model
     * @param bool $away
     * @param string $IP
     */
    public function __construct(MessengerProvider $model,
                                bool $away,
                                string $IP)
    {
        $this->model = $model;
        $this->IP = $IP;
        $this->away = $away;
    }
}
