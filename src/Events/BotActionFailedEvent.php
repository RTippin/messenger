<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\BotAction;
use Throwable;

class BotActionFailedEvent
{
    use SerializesModels;

    /**
     * @param  BotAction  $action
     * @param  Throwable  $exception
     */
    public function __construct(
        public BotAction $action,
        public Throwable $exception
    ) {
    }
}
