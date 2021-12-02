<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\DataTransferObjects\PackagedBotDTO;
use RTippin\Messenger\Models\Thread;

class InstallPackagedBotEvent
{
    use SerializesModels;

    /**
     * @var PackagedBotDTO
     */
    public PackagedBotDTO $package;

    /**
     * @var MessengerProvider
     */
    public MessengerProvider $provider;

    /**
     * @var Thread
     */
    public Thread $thread;

    /**
     * Create a new event instance.
     *
     * @param  Thread  $thread
     * @param  MessengerProvider  $provider
     * @param  PackagedBotDTO  $package
     */
    public function __construct(Thread $thread,
                                MessengerProvider $provider,
                                PackagedBotDTO $package)
    {
        $this->thread = $thread;
        $this->provider = $provider;
        $this->package = $package;
    }
}
