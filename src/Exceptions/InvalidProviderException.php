<?php

namespace RTippin\Messenger\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class InvalidProviderException extends AuthorizationException
{
    /**
     * InvalidProviderException constructor.
     *
     * @param  string  $message
     * @return void
     */
    public function __construct($message = 'Messenger provider not set or compatible.')
    {
        parent::__construct($message);
    }
}
