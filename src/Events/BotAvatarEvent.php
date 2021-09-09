<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Bot;

class BotAvatarEvent
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
     * Create a new event instance.
     *
     * @param  MessengerProvider  $provider
     * @param  Bot  $bot
     */
    public function __construct(MessengerProvider $provider, Bot $bot)
    {
        $this->provider = $provider;
        $this->bot = $bot;
    }
}
