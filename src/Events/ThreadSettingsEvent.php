<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Thread;

class ThreadSettingsEvent
{
    use SerializesModels;

    /**
     * @param  MessengerProvider  $provider
     * @param  Thread  $thread
     * @param  bool  $nameChanged
     */
    public function __construct(
        public MessengerProvider $provider,
        public Thread $thread,
        public bool $nameChanged
    ){}
}
