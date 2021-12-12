<?php

namespace RTippin\Messenger\Support;

/**
 * To authorize the end user to view and install the packaged bot, you must define the
 * 'authorize()' method and return true|false. If unauthorized, it will also hide the
 * package from appearing in the available packages list when viewing packages to
 * install. This method will trigger during a normal http request cycle, giving you
 * access to auth/sessions/etc.
 *
 * @method bool authorize()
 */
abstract class PackagedBot
{
    /**
     * Return an array containing the packages settings.
     * REQUIRED
     * - 'alias' will be used to locate and install your package.
     * - 'name' displayed to the frontend.
     * - 'description' displayed to the frontend.
     * Optional:
     * - 'avatar' Set the path to your packaged bots avatar. Upon installation, the avatar will be stored for the bot.
     * - 'cooldown' The bots' cooldown. Default of 0.
     * - 'enabled' Whether the bot starts out as enabled or disabled. Default is true.
     * - 'hide_actions' Whether the bots' actions start out as hidden. Default is false.
     *
     * @return array{alias: string, name: string, description: string, avatar: string|null, cooldown: int, enabled: bool, hide_actions: bool}
     */
    abstract public static function getSettings(): array;

    /**
     * Return the listing of bot handler classes you want to be bundled with this install.
     * The array keys must be the bot handler class. For handlers that require you to set
     * properties, or that you want to override certain defaults, you must define them as
     * the value of the handler class key represented as an associative array. If you want
     * the handler to be installed multiple times with different parameters, you can define
     * an array of arrays as the value. The key/values of your parameters must match the
     * default for a BotAction model, as well as include any parameters that are defined on
     * the handlers rules for serializing a payload.
     * BotAction handler keys include:
     * - 'enabled' Default of true.
     * - 'cooldown' : Default of 30.
     * - 'admin_only' : Default of false.
     * - 'triggers' : No default.
     * - 'match' : No Default.
     *
     * Any parameters that are already defined in the bot handler class cannot be overridden.
     * While installing, each handler will pass through a validation process, and will be
     * discarded if it fails validating.
     *
     * Handler with and without parameters:
     * - [HandlerOne::class => ['triggers' => ['one', 'two']], HandlerTwo::class, ...]
     *
     * Handler with required parameters and custom rules:
     * - [HandlerOne::class => ['triggers' => ['one'], 'cooldown' => 60, 'custom_rule' => true, 'match' => 'exact']]
     *
     * Multiple of the same handler:
     * - [HandlerTwo::class => [['triggers' => ['one']], ['triggers' => ['two']]]]
     *
     * @return array
     */
    abstract public static function installs(): array;
}
