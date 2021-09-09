<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Message;

class MessageArchivedEvent
{
    use SerializesModels;

    /**
     * @var Message
     */
    public Message $message;

    /**
     * @var MessengerProvider
     */
    public MessengerProvider $provider;

    /**
     * Create a new event instance.
     *
     * @param  MessengerProvider  $provider
     * @param  Message  $message
     */
    public function __construct(MessengerProvider $provider, Message $message)
    {
        $this->message = $message;
        $this->provider = $provider;
    }
}
