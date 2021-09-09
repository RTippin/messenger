<?php

namespace RTippin\Messenger\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class BotException extends AuthorizationException
{
    /**
     * BotException constructor.
     *
     * @param  string  $message
     */
    public function __construct(string $message = 'Bot Error.')
    {
        parent::__construct($message);
    }
}
