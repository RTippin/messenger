<?php

namespace RTippin\Messenger\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \RTippin\Messenger\MessengerTypes
 *
 * @see \RTippin\Messenger\MessengerTypes
 */
class MessengerTypes extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \RTippin\Messenger\MessengerTypes::class;
    }
}
