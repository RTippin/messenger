<?php

namespace RTippin\Messenger\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class NewThreadException extends AuthorizationException
{
    /**
     * NewThreadException constructor.
     *
     * @param  string  $message
     * @return void
     */
    public function __construct($message = 'New thread failed.')
    {
        parent::__construct($message);
    }
}
