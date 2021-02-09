<?php

namespace RTippin\Messenger\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class UploadFailedException extends HttpException
{
    /**
     * UploadFailedException constructor.
     *
     * @param string $message
     */
    public function __construct($message = 'File failed to upload.')
    {
        parent::__construct(400, $message);
    }
}
