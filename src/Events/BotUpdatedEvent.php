<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Bot;

class BotUpdatedEvent
{
    use SerializesModels;

    /**
     * @var MessengerProvider
     */
    public MessengerProvider $provider;

    /**
     * @var Bot
     */
    public Bot $bot;

    /**
     * @var string
     */
    public string $originalName;

    /**
     * Create a new event instance.
     *
     * @param  MessengerProvider  $provider
     * @param  Bot  $bot
     * @param  string  $originalName
     */
    public function __construct(MessengerProvider $provider,
                                Bot $bot,
                                string $originalName)
    {
        $this->bot = $bot;
        $this->provider = $provider;
        $this->originalName = $originalName;
    }
}
