<?php

namespace RTippin\Messenger\Tests\Fixtures;

use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Support\PackagedBot;

class SillyBotPackage extends PackagedBot
{
    const DEFAULT_INSTALLS = [
        FunBotHandler::class => [
            [
                'test' => ['one', 'two'],
                'special' => true,
            ],
            [
                'test' => ['three', 'four'],
                'special' => false,
            ],
        ],
        SillyBotHandler::class => [
            'triggers' => ['dumb'],
            'match' => MessengerBots::MATCH_EXACT,
        ],
    ];
    public static bool $enabled = true;
    public static bool $authorized = true;
    public static ?string $avatar = null;
    public static ?int $cooldown = null;
    public static ?bool $hideActions = null;
    public static array $installs = self::DEFAULT_INSTALLS;

    public static function getSettings(): array
    {
        return [
            'alias' => 'silly_package',
            'name' => 'Silly Package',
            'description' => 'Silly package description.',
            'enabled' => self::$enabled,
            'avatar' => self::$avatar,
            'cooldown' => self::$cooldown,
            'hide_actions' => self::$hideActions,
        ];
    }

    public static function installs(): array
    {
        return self::$installs;
    }

    public function authorize(): bool
    {
        return self::$authorized;
    }

    public static function reset(): void
    {
        self::$enabled = true;
        self::$authorized = true;
        self::$avatar = null;
        self::$cooldown = null;
        self::$hideActions = null;
        self::$installs = self::DEFAULT_INSTALLS;
    }
}
