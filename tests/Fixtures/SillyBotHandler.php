<?php

namespace RTippin\Messenger\Tests\Fixtures;

use RTippin\Messenger\Actions\Bots\BotActionHandler;

class SillyBotHandler extends BotActionHandler
{
    public static bool $authorized = false;

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
        $this->composer()->message('Testing Silly. '.$this->senderIp);

        $this->releaseCooldown();
    }

    public function authorize(): bool
    {
        return self::$authorized;
    }

    public static function reset(): void
    {
        self::$authorized = false;
    }
}
