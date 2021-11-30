<?php

namespace RTippin\Messenger\Tests\Fixtures;

use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Support\PackagedBot;

class SillyBotPackage extends PackagedBot
{
    public static function getSettings(): array
    {
        return [
            'alias' => 'silly_bot_package',
            'name' => 'Mr. Silly Package',
            'description' => 'Silly package description.',
            'avatar' => null,
        ];
    }

    public static function installs(): array
    {
        return [
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
    }
}
