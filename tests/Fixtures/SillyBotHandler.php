<?php

namespace RTippin\Messenger\Tests\Fixtures;

use RTippin\Messenger\Support\BotActionHandler;

class SillyBotHandler extends BotActionHandler
{
    public static bool $unique = true;
    public static bool $authorized = false;
    public static ?string $match = null;
    public static ?array $triggers = null;

    public static function getSettings(): array
    {
        return [
            'alias' => 'silly_bot',
            'description' => 'This is a silly bot.',
            'name' => 'Silly Bot',
            'unique' => self::$unique,
            'match' => self::$match,
            'triggers' => self::$triggers,
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
        self::$unique = true;
        self::$authorized = false;
        self::$match = null;
        self::$triggers = null;
    }
}
