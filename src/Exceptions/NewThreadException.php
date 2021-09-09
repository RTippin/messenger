<?php

namespace RTippin\Messenger\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class NewThreadException extends AuthorizationException
{
    /**
     * NewThreadException constructor.
     *
     * @param  string  $message
     */
    public function __construct(string $message = 'New thread failed.')
    {
        parent::__construct($message);
    }
}
