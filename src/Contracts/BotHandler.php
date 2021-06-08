<?php

namespace RTippin\Messenger\Contracts;

use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;

interface BotHandler
{
    /**
     * Executes the bots action, allowing variable number of params.
     *
     * @param BotAction $action
     * @param Message $message
     * @param string $matchingTrigger
     * @return void
     */
    public function execute(BotAction $action, Message $message, string $matchingTrigger): void;
}
