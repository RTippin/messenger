<?php

namespace RTippin\Messenger\Tests\Fixtures;

use RTippin\Messenger\Support\PackagedBot;

class FunBotPackage extends PackagedBot
{
    public static function name(): string
    {
        return 'Mr. Fun';
    }

    public static function description(): string
    {
        return 'Fun description.';
    }

    public static function installs(): array
    {
        return [
            FunBotHandler::class,
        ];
    }
}
