<?php

namespace RTippin\Messenger\Tests\Fixtures;

use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Support\BotActionHandler;

class BrokenBotHandler extends BotActionHandler
{
    public static function getSettings(): array
    {
        return [
            'alias' => 'broken_bot',
            'description' => 'This is a broken bot.',
            'name' => 'Broken Bot',
            'unique' => true,
        ];
    }

    public function handle(): void
    {
        throw new BotException('Busted.');
    }
}
