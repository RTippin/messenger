<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Message;

class EmbedsRemovedEvent
{
    use SerializesModels;

    /**
     * @var MessengerProvider
     */
    public MessengerProvider $provider;

    /**
     * @var Message
     */
    public Message $message;

    /**
     * Create a new event instance.
     *
     * @param  MessengerProvider  $provider
     * @param  Message  $message
     */
    public function __construct(MessengerProvider $provider, Message $message)
    {
        $this->provider = $provider;
        $this->message = $message;
    }
}
