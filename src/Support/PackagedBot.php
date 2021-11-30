<?php

namespace RTippin\Messenger\Support;

/**
 * To authorize the end user to view and install the packaged bot, you must define the
 * 'authorize()' method and return true|false. If unauthorized, it will also hide the
 * package from appearing in the available packages list when viewing packages to
 * install. This method will be called during a normal http request cycle, giving
 * you access to auth/sessions/etc.
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
     * 'avatar' Set the path to your packaged bots avatar. Upon installation, the avatar will be stored for the bot.
     * 'cooldown' The bots' cooldown. Default of 0.
     * 'enabled' Whether the bot starts out as enabled or disabled. Default is true.
     * 'hide_actions' Whether the bots' actions start out as hidden. Default is false.
     *
     * @return array{alias: string, name: string, description: string, avatar: string, cooldown: int, enabled: bool, hide_actions: bool}
     */
    abstract public static function getSettings(): array;

    /**
     * @return array
     */
    abstract public static function installs(): array;
}
