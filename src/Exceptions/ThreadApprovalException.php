<?php

namespace RTippin\Messenger\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class ThreadApprovalException extends AuthorizationException
{
    /**
     * ThreadApprovalException constructor.
     *
     * @param  string  $message
     * @return void
     */
    public function __construct($message = 'Not authorized to approve that conversation.')
    {
        parent::__construct($message);
    }
}
