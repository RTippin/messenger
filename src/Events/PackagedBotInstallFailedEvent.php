<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\DataTransferObjects\PackagedBotDTO;
use RTippin\Messenger\Models\Thread;
use Throwable;

class PackagedBotInstallFailedEvent
{
    use SerializesModels;

    /**
     * @var Throwable
     */
    public Throwable $exception;

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
     * @param  Throwable  $exception
     * @param  PackagedBotDTO  $packagedBot
     * @param  Thread  $thread
     * @param  MessengerProvider  $provider
     */
    public function __construct(Throwable $exception,
                                PackagedBotDTO $packagedBot,
                                Thread $thread,
                                MessengerProvider $provider)
    {
        $this->exception = $exception;
        $this->packagedBot = $packagedBot;
        $this->thread = $thread;
        $this->provider = $provider;
    }
}
