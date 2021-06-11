<?php

namespace RTippin\Messenger\Tests\Messenger;

use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Facades\MessengerBots as BotsFacade;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Tests\MessengerTestCase;

class MessengerBotsTest extends MessengerTestCase
{
    private MessengerBots $bots;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bots = app(MessengerBots::class);
    }

    /** @test */
    public function messenger_bots_facade_same_instance_as_container()
    {
        $this->assertSame($this->bots, BotsFacade::getInstance());
        $this->assertSame($this->bots, app('messenger-bots'));
    }

    /** @test */
    public function it_can_set_bot_handlers()
    {
        $handlers = [
            TestBot::class,
            TestBotTwo::class,
        ];

        $this->bots->setHandlers($handlers);

        $this->assertSame($handlers, $this->bots->getHandlers());
    }

    /** @test */
    public function it_can_get_bot_aliases()
    {
        $handlers = [
            TestBot::class,
            TestBotTwo::class,
        ];
        $aliases = [
            'fun_bot',
            'silly_bot',
        ];

        $this->bots->setHandlers($handlers);

        $this->assertSame($aliases, $this->bots->getAliases());
    }

    /** @test */
    public function it_ignores_invalid_and_missing_bot_handlers()
    {
        $actions = [
            TestBot::class,
            InvalidBotAction::class,
            MissingAction::class,
        ];

        $this->bots->setHandlers($actions);

        $this->assertSame([TestBot::class], $this->bots->getHandlers());
    }

    /** @test */
    public function it_checks_if_valid_handler()
    {
        $handlers = [
            TestBot::class,
            TestBotTwo::class,
            InvalidBotAction::class,
        ];

        $this->bots->setHandlers($handlers);

        $this->assertTrue($this->bots->isValidHandler(TestBot::class));
        $this->assertTrue($this->bots->isValidHandler(TestBotTwo::class));
        $this->assertFalse($this->bots->isValidHandler(InvalidBotAction::class));
    }

    /** @test */
    public function it_checks_if_valid_handler_using_alias()
    {
        $handlers = [
            TestBot::class,
            TestBotTwo::class,
            InvalidBotAction::class,
        ];

        $this->bots->setHandlers($handlers);

        $this->assertTrue($this->bots->isValidHandler('fun_bot'));
        $this->assertTrue($this->bots->isValidHandler('silly_bot'));
        $this->assertFalse($this->bots->isValidHandler('invalid'));
    }
}

class TestBot extends BotActionHandler
{
    public static function getAlias(): string
    {
        return 'fun_bot';
    }
    public static function getDescription(): string
    {
        return 'This is a fun bot.';
    }
    public static function getName(): string
    {
        return 'Fun Bot';
    }
    public function handle(): void {}
    public function rules(): array
    {
        return ['test' => 'required'];
    }
}

class TestBotTwo extends BotActionHandler
{
    public static function getAlias(): string
    {
        return 'silly_bot';
    }
    public static function getDescription(): string
    {
        return 'This is a silly bot.';
    }
    public static function getName(): string
    {
        return 'Silly Bot';
    }
    public function handle(): void {}
    public function rules(): array
    {
        return ['test_two' => 'required'];
    }
}

class InvalidBotAction
{
    public function handle(): void
    {
        //
    }
}
