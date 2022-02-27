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
     * @param  PackagedBotDTO  $packagedBot
     * @param  Thread  $thread
     * @param  MessengerProvider  $provider
     */
    public function __construct(
        public PackagedBotDTO $packagedBot,
        public Thread $thread,
        public MessengerProvider $provider
    ) {
    }
}
