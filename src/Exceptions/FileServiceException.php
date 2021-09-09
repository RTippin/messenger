<?php

namespace RTippin\Messenger\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class FileServiceException extends HttpException
{
    /**
     * FileServiceException constructor.
     *
     * @param  string|null  $message
     */
    public function __construct(?string $message = null)
    {
        parent::__construct(400, $message ?? 'File failed to upload.');
    }
}
