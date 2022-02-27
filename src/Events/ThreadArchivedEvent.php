<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Thread;

class ThreadArchivedEvent
{
    use SerializesModels;

    /**
     * @param  null|MessengerProvider  $provider
     * @param  Thread  $thread
     */
    public function __construct(
        public ?MessengerProvider $provider,
        public Thread $thread
    ){}
}
