<?php

namespace RTippin\Messenger\Events;

use Illuminate\Queue\SerializesModels;
use RTippin\Messenger\Models\BotAction;
use Throwable;

class BotActionFailedEvent
{
    use SerializesModels;

    /**
     * @var BotAction
     */
    public BotAction $action;

    /**
     * @var Throwable
     */
    public Throwable $exception;

    /**
     * Create a new event instance.
     *
     * @param  BotAction  $action
     * @param  Throwable  $exception
     */
    public function __construct(BotAction $action, Throwable $exception)
    {
        $this->action = $action;
        $this->exception = $exception;
    }
}
