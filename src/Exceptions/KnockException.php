<?php

namespace RTippin\Messenger\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class KnockException extends AuthorizationException
{
    /**
     * Create a new call exception.
     *
     * @param  string  $message
     * @return void
     */
    public function __construct($message = 'Knock knock denied.')
    {
        parent::__construct($message);
    }
}
