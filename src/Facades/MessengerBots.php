<?php

namespace RTippin\Messenger\Facades;

use Illuminate\Support\Facades\Facade;
use RTippin\Messenger\Models\BotAction;

/**
 * @method static array getHandlerClasses()
 * @method static array getUniqueHandlerClasses()
 * @method static array|null getHandlerSettings(?string $handlerOrAlias = null)
 * @method static array getAuthorizedHandlers()
 * @method static array getAliases()
 * @method static array getMatchMethods()
 * @method static string|null getMatchDescription(?string $match = null)
 * @method static string|null findHandler(string $handlerOrAlias)
 * @method static bool isValidHandler(?string $handlerOrAlias)
 * @method static void registerHandlers(array $handlers, bool $overwrite = false)
 * @method static \RTippin\Messenger\MessengerBots getInstance()
 * @method static void flush()
 * @method static \RTippin\Messenger\Actions\Bots\BotActionHandler initializeHandler(string $handlerOrAlias)
 * @method static bool isActiveHandlerSet()
 * @method static \RTippin\Messenger\Actions\Bots\BotActionHandler|null getActiveHandler()
 * @method static array resolveHandlerData(array $data, ?BotAction $action = null)
 *
 * @mixin \RTippin\Messenger\MessengerBots
 *
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
