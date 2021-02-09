<?php

namespace RTippin\Messenger\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class NewCallException extends AuthorizationException
{
    /**
     * NewCallException constructor.
     *
     * @param  string  $message
     * @return void
     */
    public function __construct($message = 'New call failed.')
    {
        parent::__construct($message);
    }
}
