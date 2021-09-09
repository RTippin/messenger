<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Call;

class CallIgnoredEvent
{
    use SerializesModels;

    /**
     * @var Call
     */
    public Call $call;

    /**
     * @var MessengerProvider
     */
    public MessengerProvider $provider;

    /**
     * Create a new event instance.
     *
     * @param  Call  $call
     * @param  MessengerProvider  $provider
     */
    public function __construct(Call $call, MessengerProvider $provider)
    {
        $this->call = $call;
        $this->provider = $provider;
    }
}
