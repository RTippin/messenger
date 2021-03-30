<?php

namespace RTippin\Messenger\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class ReactionException extends AuthorizationException
{
    /**
     * KnockException constructor.
     *
     * @param  string  $message
     * @return void
     */
    public function __construct($message = 'Reaction was not authorized.')
    {
        parent::__construct($message);
    }
}
