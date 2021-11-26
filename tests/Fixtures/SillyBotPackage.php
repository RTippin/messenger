<?php

namespace RTippin\Messenger\Tests\Fixtures;

use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Support\PackagedBot;

class SillyBotPackage extends PackagedBot
{
    public static function name(): string
    {
        return 'Mr. Silly';
    }

    public static function description(): string
    {
        return 'Silly description.';
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
