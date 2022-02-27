<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;

class CallStartedEvent
{
    use SerializesModels;

    /**
     * @param  Call  $call
     * @param  Thread  $thread
     */
    public function __construct(
        public Call $call,
        public Thread $thread
    ){}
}
