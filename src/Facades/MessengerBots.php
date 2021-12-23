<?php

namespace RTippin\Messenger\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array getMatchMethods()
 * @method static string|null getMatchDescription(?string $match = null)
 * @method static \RTippin\Messenger\MessengerBots getInstance()
 * @method static void flush()
 * @method static bool shouldAuthorize(?bool $shouldAuthorize = null)
 * @method static void registerHandlers(array $handlers, bool $overwrite = false)
 * @method static array getHandlerClasses()
 * @method static array getUniqueHandlerClasses()
 * @method static \RTippin\Messenger\DataTransferObjects\BotActionHandlerDTO|\Illuminate\Support\Collection|null getHandlers(?string $handlerOrAlias = null)
 * @method static \RTippin\Messenger\DataTransferObjects\BotActionHandlerDTO|null getHandler(string $handlerOrAlias)
 * @method static \Illuminate\Support\Collection getAuthorizedHandlers()
 * @method static array getHandlerAliases()
 * @method static string|null findHandler(string $handlerOrAlias)
 * @method static bool isValidHandler(?string $handlerOrAlias)
 * @method static \RTippin\Messenger\Support\BotActionHandler initializeHandler(string $handlerOrAlias)
 * @method static bool isActiveHandlerSet()
 * @method static \RTippin\Messenger\Support\BotActionHandler|null getActiveHandler()
 * @method static void registerPackagedBots(array $packagedBots, bool $overwrite = false)
 * @method static array getPackagedBotClasses()
 * @method static \RTippin\Messenger\DataTransferObjects\PackagedBotDTO|\Illuminate\Support\Collection|null getPackagedBots(?string $packageOrAlias = null)
 * @method static \RTippin\Messenger\DataTransferObjects\PackagedBotDTO|null getPackagedBot(string $packageOrAlias)
 * @method static \Illuminate\Support\Collection getAuthorizedPackagedBots()
 * @method static array getPackagedBotAliases()
 * @method static string|null findPackagedBot(string $packageOrAlias)
 * @method static bool isValidPackagedBot(?string $packageOrAlias)
 * @method static \RTippin\Messenger\Support\PackagedBot initializePackagedBot(string $packageOrAlias)
 * @method static bool authorizeHandler(\RTippin\Messenger\DataTransferObjects\BotActionHandlerDTO $handler)
 * @method static bool authorizePackagedBot(\RTippin\Messenger\DataTransferObjects\PackagedBotDTO $package)
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
