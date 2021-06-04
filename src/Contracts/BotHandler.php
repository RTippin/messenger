<?php

namespace RTippin\Messenger\Contracts;

use RTippin\Messenger\Models\Action;
use RTippin\Messenger\Models\Message;

interface BotHandler
{
    /**
     * Executes the bots action, allowing variable number of params.
     *
     * @param Action $action
     * @param Message $message
     * @return void
     */
    public function execute(Action $action, Message $message): void;
}
