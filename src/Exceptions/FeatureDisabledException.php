<?php

namespace RTippin\Messenger\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class FeatureDisabledException extends AuthorizationException
{
    /**
     * Create a new feature disabled exception.
     *
     * @param  string  $message
     * @return void
     */
    public function __construct($message = 'That feature is currently disabled.')
    {
        parent::__construct($message);
    }
}
