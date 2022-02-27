<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Thread;

class ThreadApprovalEvent
{
    use SerializesModels;

    /**
     * @param  Thread  $thread
     * @param  MessengerProvider  $provider
     * @param  bool  $approved
     */
    public function __construct(
        public MessengerProvider $provider,
        public Thread $thread,
        public bool $approved
    ) {
    }
}
