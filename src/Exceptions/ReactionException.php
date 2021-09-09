<?php

namespace RTippin\Messenger\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class ReactionException extends AuthorizationException
{
    /**
     * KnockException constructor.
     *
     * @param  string  $message
     */
    public function __construct(string $message = 'Reaction was not authorized.')
    {
        parent::__construct($message);
    }
}
