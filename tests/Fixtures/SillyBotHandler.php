<?php

namespace RTippin\Messenger\Tests\Fixtures;

use RTippin\Messenger\Actions\Bots\BotActionHandler;

class SillyBotHandler extends BotActionHandler
{
    public static function getSettings(): array
    {
        return [
            'alias' => 'silly_bot',
            'description' => 'This is a silly bot.',
            'name' => 'Silly Bot',
            'unique' => true,
        ];
    }

    public function handle(): void
    {
        //
    }

    public function authorize(): bool
    {
        return false;
    }
}
