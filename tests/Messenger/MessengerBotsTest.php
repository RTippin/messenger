<?php

namespace RTippin\Messenger\Tests\Messenger;

use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\Exceptions\BotException;
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

        $this->assertSame($handlers, $this->bots->getHandlerClasses());
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
    public function it_can_get_all_bot_settings()
    {
        $handlers = [
            TestBot::class,
            TestBotTwo::class,
        ];
        $settings = [
            [
                'alias' => 'fun_bot',
                'description' => 'This is a fun bot.',
                'name' => 'Fun Bot',
                'unique' => false,
            ],
            [
                'alias' => 'silly_bot',
                'description' => 'This is a silly bot.',
                'name' => 'Silly Bot',
                'unique' => true,
            ],
        ];

        $this->bots->setHandlers($handlers);

        $this->assertSame($settings, $this->bots->getHandlerSettings());
    }

    /** @test */
    public function it_can_get_single_bot_settings()
    {
        $handlers = [
            TestBot::class,
            TestBotTwo::class,
        ];
        $settings = [
            'alias' => 'silly_bot',
            'description' => 'This is a silly bot.',
            'name' => 'Silly Bot',
            'unique' => true,
        ];

        $this->bots->setHandlers($handlers);

        $this->assertSame($settings, $this->bots->getHandlerSettings('silly_bot'));
        $this->assertSame($settings, $this->bots->getHandlerSettings(TestBotTwo::class));
        $this->assertNull($this->bots->getHandlerSettings('unknown'));
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

        $this->assertSame([TestBot::class], $this->bots->getHandlerClasses());
    }

    /** @test */
    public function it_can_set_handlers_adding_ones_to_existing_and_ignoring_duplicate()
    {
        $actions = [
            TestBot::class,
            TestBotTwo::class,
        ];

        $this->bots->setHandlers([TestBot::class]);
        $this->bots->setHandlers([TestBotTwo::class]);
        $this->bots->setHandlers([TestBot::class]);

        $this->assertSame($actions, $this->bots->getHandlerClasses());
    }

    /** @test */
    public function it_can_reset_handlers()
    {
        $this->bots->setHandlers([
            TestBot::class,
            TestBotTwo::class,
        ]);

        $this->assertCount(2, $this->bots->getHandlerClasses());

        $this->bots->setHandlers([], true);

        $this->assertCount(0, $this->bots->getHandlerClasses());
    }

    /** @test */
    public function it_can_overwrite_existing_handlers()
    {
        $this->bots->setHandlers([
            TestBot::class,
            TestBotTwo::class,
        ]);

        $this->assertCount(2, $this->bots->getHandlerClasses());

        $this->bots->setHandlers([TestBotTwo::class], true);

        $this->assertCount(1, $this->bots->getHandlerClasses());
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

    /** @test */
    public function it_can_initialize_bot_using_class()
    {
        $this->bots->setHandlers([TestBot::class]);

        $this->assertInstanceOf(TestBot::class, $this->bots->initializeHandler(TestBot::class));
    }

    /** @test */
    public function it_can_initialize_bot_using_alias()
    {
        $this->bots->setHandlers([TestBot::class]);

        $this->assertInstanceOf(TestBot::class, $this->bots->initializeHandler('fun_bot'));
    }

    /** @test */
    public function it_throws_exception_if_invalid_bot()
    {
        $this->expectException(BotException::class);
        $this->expectExceptionMessage('Invalid bot handler.');

        $this->bots->setHandlers([TestBot::class]);
        $this->bots->initializeHandler(TestBotTwo::class);
    }

    /** @test */
    public function it_can_access_initialized_bot()
    {
        $this->bots->setHandlers([TestBot::class]);
        $this->bots->initializeHandler(TestBot::class);

        $this->assertInstanceOf(TestBot::class, $this->bots->getActiveHandler());
        $this->assertTrue($this->bots->isActiveHandlerSet());
    }

    /** @test */
    public function it_returns_null_when_no_bot_initialized()
    {
        $this->bots->setHandlers([TestBot::class]);

        $this->assertNull($this->bots->getActiveHandler());
        $this->assertFalse($this->bots->isActiveHandlerSet());
    }
}

class TestBot extends BotActionHandler
{
    public static function getSettings(): array
    {
        return [
            'alias' => 'fun_bot',
            'description' => 'This is a fun bot.',
            'name' => 'Fun Bot',
            'unique' => false,
        ];
    }

    public function handle(): void
    {
        //
    }

    public function rules(): array
    {
        return ['test' => 'required'];
    }
}

class TestBotTwo extends BotActionHandler
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
}

class InvalidBotAction
{
    public function handle(): void
    {
        //
    }
}
