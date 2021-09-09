<?php

namespace RTippin\Messenger\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;

class ThreadApprovalException extends AuthorizationException
{
    /**
     * ThreadApprovalException constructor.
     *
     * @param  string  $message
     */
    public function __construct(string $message = 'Not authorized to approve that conversation.')
    {
        parent::__construct($message);
    }
}
