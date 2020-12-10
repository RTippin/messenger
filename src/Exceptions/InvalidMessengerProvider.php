<?php

namespace RTippin\Messenger\Exceptions;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class InvalidMessengerProvider extends Exception
{
    const Message = 'Messenger provider not set or compatible';

    /**
     * Render the exception into an HTTP response.
     *
     * @param Request $request
     * @return void
     * @throws AuthorizationException
     * @noinspection PhpMissingParamTypeInspection
     * @noinspection PhpUnusedParameterInspection
     */
    public function render($request)
    {
        throw new AuthorizationException(self::Message);
    }
}
