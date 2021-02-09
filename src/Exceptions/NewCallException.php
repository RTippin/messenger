<?php

namespace RTippin\Messenger\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class NewCallException extends AuthorizationException
{
    /**
     * Create a new call exception.
     *
     * @param  string  $message
     * @return void
     */
    public function __construct($message = 'New call failed.')
    {
        parent::__construct($message);
    }
}
