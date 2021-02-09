<?php

namespace RTippin\Messenger\Exceptions;

use Exception;

class CallBrokerException extends Exception
{
    /**
     * CallBrokerException constructor.
     * @param string $message
     */
    public function __construct($message = 'Call broker failed.')
    {
        parent::__construct($message);
    }
}
