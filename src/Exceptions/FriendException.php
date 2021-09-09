<?php

namespace RTippin\Messenger\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class FriendException extends AuthorizationException
{
    /**
     * FriendException constructor.
     *
     * @param  string  $message
     */
    public function __construct(string $message = 'Friend action denied.')
    {
        parent::__construct($message);
    }
}
