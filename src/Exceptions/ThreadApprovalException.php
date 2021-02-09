<?php

namespace RTippin\Messenger\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class ThreadApprovalException extends AuthorizationException
{
    /**
     * Create a new call exception.
     *
     * @param  string  $message
     * @return void
     */
    public function __construct($message = 'Not authorized to approve that conversation.')
    {
        parent::__construct($message);
    }
}
