<?php

namespace RTippin\Messenger\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class FriendException extends AuthorizationException
{
    /**
     * FriendException constructor.
     *
     * @param  string  $message
     * @return void
     */
    public function __construct($message = 'Friend action denied.')
    {
        parent::__construct($message);
    }
}
