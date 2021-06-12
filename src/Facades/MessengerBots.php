<?php

namespace RTippin\Messenger\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array getHandlerClasses()
 * @method static \RTippin\Messenger\MessengerBots setHandlers(array $actions, bool $overwrite = false)
 * @method static \RTippin\Messenger\MessengerBots getInstance()
 *
 * @mixin \RTippin\Messenger\MessengerBots
 * @see \RTippin\Messenger\MessengerBots
 */
class MessengerBots extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \RTippin\Messenger\MessengerBots::class;
    }
}
