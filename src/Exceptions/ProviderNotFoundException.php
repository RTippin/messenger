<?php

namespace RTippin\Messenger\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProviderNotFoundException extends NotFoundHttpException
{
    /**
     * ProviderNotFoundException constructor.
     *
     * @param string $message
     */
    public function __construct($message = 'We were unable to locate the recipient you requested.')
    {
        parent::__construct($message);
    }
}
