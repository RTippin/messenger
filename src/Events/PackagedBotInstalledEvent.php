<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\DataTransferObjects\PackagedBotDTO;
use RTippin\Messenger\Models\Thread;

class PackagedBotInstalledEvent
{
    use SerializesModels;

    /**
     * @var PackagedBotDTO
     */
    public PackagedBotDTO $packagedBot;

    /**
     * @var Thread
     */
    public Thread $thread;

    /**
     * @var MessengerProvider
     */
    public MessengerProvider $provider;

    /**
     * Create a new event instance.
     *
     * @param  PackagedBotDTO  $packagedBot
     * @param  Thread  $thread
     * @param  MessengerProvider  $provider
     */
    public function __construct(PackagedBotDTO $packagedBot,
                                Thread $thread,
                                MessengerProvider $provider)
    {
        $this->packagedBot = $packagedBot;
        $this->thread = $thread;
        $this->provider = $provider;
    }
}
