<?php

namespace RTippin\Messenger\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class KnockException extends AuthorizationException
{
    /**
     * KnockException constructor.
     *
     * @param  string  $message
     */
    public function __construct(string $message = 'Knock knock denied.')
    {
        parent::__construct($message);
    }
}
