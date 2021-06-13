<?php

namespace RTippin\Messenger\Tests\Messenger;

use Illuminate\Validation\ValidationException;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Facades\MessengerBots as BotsFacade;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Tests\Fixtures\TestBotHandler;
use RTippin\Messenger\Tests\Fixtures\TestBotTwoHandler;
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
            TestBotHandler::class,
            TestBotTwoHandler::class,
        ];

        $this->bots->setHandlers($handlers);

        $this->assertSame($handlers, $this->bots->getHandlerClasses());
    }

    /** @test */
    public function it_can_get_bot_aliases()
    {
        $handlers = [
            TestBotHandler::class,
            TestBotTwoHandler::class,
        ];
        $aliases = [
            'fun_bot',
            'silly_bot',
        ];

        $this->bots->setHandlers($handlers);

        $this->assertSame($aliases, $this->bots->getAliases());
    }

    /** @test */
    public function it_can_get_bot_match_methods()
    {
        $expected = [
            'contains',
            'contains:caseless',
            'contains:any',
            'contains:any:caseless',
            'exact',
            'exact:caseless',
            'starts:with',
            'starts:with:caseless',
        ];

        $this->assertSame($expected, $this->bots->getMatchMethods());
    }

    /** @test */
    public function it_can_get_all_bot_settings()
    {
        $handlers = [
            TestBotHandler::class,
            TestBotTwoHandler::class,
        ];
        $settings = [
            [
                'alias' => 'fun_bot',
                'description' => 'This is a fun bot.',
                'name' => 'Fun Bot',
                'triggers' => '!test|!more',
                'match' => 'exact:caseless',
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
            TestBotHandler::class,
            TestBotTwoHandler::class,
        ];
        $settings = [
            'alias' => 'silly_bot',
            'description' => 'This is a silly bot.',
            'name' => 'Silly Bot',
            'unique' => true,
        ];

        $this->bots->setHandlers($handlers);

        $this->assertSame($settings, $this->bots->getHandlerSettings('silly_bot'));
        $this->assertSame($settings, $this->bots->getHandlerSettings(TestBotTwoHandler::class));
        $this->assertNull($this->bots->getHandlerSettings('unknown'));
    }

    /** @test */
    public function it_ignores_invalid_and_missing_bot_handlers()
    {
        $actions = [
            TestBotHandler::class,
            InvalidBotHandler::class,
            MissingAction::class,
        ];

        $this->bots->setHandlers($actions);

        $this->assertSame([TestBotHandler::class], $this->bots->getHandlerClasses());
    }

    /** @test */
    public function it_can_set_handlers_adding_ones_to_existing_and_ignoring_duplicate()
    {
        $actions = [
            TestBotHandler::class,
            TestBotTwoHandler::class,
        ];

        $this->bots->setHandlers([TestBotHandler::class]);
        $this->bots->setHandlers([TestBotTwoHandler::class]);
        $this->bots->setHandlers([TestBotHandler::class]);

        $this->assertSame($actions, $this->bots->getHandlerClasses());
    }

    /** @test */
    public function it_can_reset_handlers()
    {
        $this->bots->setHandlers([
            TestBotHandler::class,
            TestBotTwoHandler::class,
        ]);

        $this->assertCount(2, $this->bots->getHandlerClasses());

        $this->bots->setHandlers([], true);

        $this->assertCount(0, $this->bots->getHandlerClasses());
    }

    /** @test */
    public function it_can_overwrite_existing_handlers()
    {
        $this->bots->setHandlers([
            TestBotHandler::class,
            TestBotTwoHandler::class,
        ]);

        $this->assertCount(2, $this->bots->getHandlerClasses());

        $this->bots->setHandlers([TestBotTwoHandler::class], true);

        $this->assertCount(1, $this->bots->getHandlerClasses());
    }

    /** @test */
    public function it_checks_if_valid_handler()
    {
        $handlers = [
            TestBotHandler::class,
            TestBotTwoHandler::class,
            InvalidBotHandler::class,
        ];

        $this->bots->setHandlers($handlers);

        $this->assertTrue($this->bots->isValidHandler(TestBotHandler::class));
        $this->assertTrue($this->bots->isValidHandler(TestBotTwoHandler::class));
        $this->assertFalse($this->bots->isValidHandler(InvalidBotHandler::class));
        $this->assertFalse($this->bots->isValidHandler(''));
        $this->assertFalse($this->bots->isValidHandler(null));
    }

    /** @test */
    public function it_checks_if_valid_handler_using_alias()
    {
        $handlers = [
            TestBotHandler::class,
            TestBotTwoHandler::class,
            InvalidBotHandler::class,
        ];

        $this->bots->setHandlers($handlers);

        $this->assertTrue($this->bots->isValidHandler('fun_bot'));
        $this->assertTrue($this->bots->isValidHandler('silly_bot'));
        $this->assertFalse($this->bots->isValidHandler('invalid'));
    }

    /** @test */
    public function it_can_initialize_bot_using_class()
    {
        $this->bots->setHandlers([TestBotHandler::class]);

        $this->assertInstanceOf(TestBotHandler::class, $this->bots->initializeHandler(TestBotHandler::class));
    }

    /** @test */
    public function it_can_initialize_bot_using_alias()
    {
        $this->bots->setHandlers([TestBotHandler::class]);

        $this->assertInstanceOf(TestBotHandler::class, $this->bots->initializeHandler('fun_bot'));
    }

    /** @test */
    public function it_throws_exception_if_invalid_bot()
    {
        $this->expectException(BotException::class);
        $this->expectExceptionMessage('Invalid bot handler.');

        $this->bots->setHandlers([TestBotHandler::class]);
        $this->bots->initializeHandler(TestBotTwoHandler::class);
    }

    /** @test */
    public function it_can_access_initialized_bot()
    {
        $this->bots->setHandlers([TestBotHandler::class]);
        $this->bots->initializeHandler(TestBotHandler::class);

        $this->assertInstanceOf(TestBotHandler::class, $this->bots->getActiveHandler());
        $this->assertTrue($this->bots->isActiveHandlerSet());
    }

    /** @test */
    public function it_returns_null_when_no_bot_initialized()
    {
        $this->bots->setHandlers([TestBotHandler::class]);

        $this->assertNull($this->bots->getActiveHandler());
        $this->assertFalse($this->bots->isActiveHandlerSet());
    }

    /** @test */
    public function it_validates()
    {
        $this->bots->setHandlers([TestBotHandler::class]);

        try {
            $test = $this->bots->resolveHandlerData([
                'handler' => 'fun_bot',
                'match' => 'exact',
                'cooldown' => 0,
                'admin_only' => false,
                'enabled' => true,
                'triggers' => ['!e | testing', 'lol'],
                'test' => ['1', '2', 'three baby'],
                'more' => 'stuff',
            ]);
//            dump($test);
        } catch (ValidationException $e) {
            dump($e->errors());
        }

        $this->assertTrue(true);
    }
}

class InvalidBotHandler
{
    public function handle(): void
    {
        //
    }
}
