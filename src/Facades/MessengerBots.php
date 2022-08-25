<?php

namespace RTippin\Messenger\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use RTippin\Messenger\DataTransferObjects\BotActionHandlerDTO;
use RTippin\Messenger\DataTransferObjects\PackagedBotDTO;
use RTippin\Messenger\Support\BotActionHandler;
use RTippin\Messenger\Support\PackagedBot;

/**
 * @method static array getMatchMethods()
 * @method static string|null getMatchDescription(?string $match = null)
 * @method static \RTippin\Messenger\MessengerBots getInstance()
 * @method static void flush()
 * @method static bool shouldAuthorize(?bool $shouldAuthorize = null)
 * @method static void registerHandlers(array $handlers, bool $overwrite = false)
 * @method static array getHandlerClasses()
 * @method static array getUniqueHandlerClasses()
 * @method static BotActionHandlerDTO|Collection|null getHandlers(?string $handlerOrAlias = null)
 * @method static BotActionHandlerDTO|null getHandler(string $handlerOrAlias)
 * @method static Collection getAuthorizedHandlers()
 * @method static array getHandlerAliases()
 * @method static string|null findHandler(string $handlerOrAlias)
 * @method static bool isValidHandler(?string $handlerOrAlias)
 * @method static BotActionHandler initializeHandler(string $handlerOrAlias)
 * @method static bool isActiveHandlerSet()
 * @method static BotActionHandler|null getActiveHandler()
 * @method static void registerPackagedBots(array $packagedBots, bool $overwrite = false)
 * @method static array getPackagedBotClasses()
 * @method static PackagedBotDTO|Collection|null getPackagedBots(?string $packageOrAlias = null)
 * @method static PackagedBotDTO|null getPackagedBot(string $packageOrAlias)
 * @method static Collection getAuthorizedPackagedBots()
 * @method static array getPackagedBotAliases()
 * @method static string|null findPackagedBot(string $packageOrAlias)
 * @method static bool isValidPackagedBot(?string $packageOrAlias)
 * @method static PackagedBot initializePackagedBot(string $packageOrAlias)
 * @method static bool authorizeHandler(BotActionHandlerDTO $handler)
 * @method static bool authorizePackagedBot(PackagedBotDTO $package)
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
