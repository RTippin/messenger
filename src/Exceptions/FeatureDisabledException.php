<?php

namespace RTippin\Messenger\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class FeatureDisabledException extends AuthorizationException
{
    /**
     * FeatureDisabledException constructor.
     *
     * @param  string  $message
     */
    public function __construct(string $message = 'That feature is currently disabled.')
    {
        parent::__construct($message);
    }
}
