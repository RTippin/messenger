<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\Call;

class CallEndedEvent
{
    use SerializesModels;

    /**
     * @var Call
     */
    public Call $call;

    /**
     * Create a new event instance.
     *
     * @param Call $call
     */
    public function __construct(Call $call)
    {
        $this->call = $call;
    }
}
