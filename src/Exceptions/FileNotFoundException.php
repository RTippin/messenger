<?php

namespace RTippin\Messenger\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FileNotFoundException extends NotFoundHttpException
{
    /**
     * FileNotFoundException constructor.
     *
     * @param  string|null  $fileName
     */
    public function __construct(?string $fileName = null)
    {
        $name = $fileName ?? 'unknown file';

        parent::__construct("File not found: $name.");
    }
}
