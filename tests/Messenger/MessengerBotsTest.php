<?php

namespace RTippin\Messenger\Tests\Messenger;

use InvalidArgumentException;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Facades\MessengerBots as BotsFacade;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Tests\Fixtures\BrokenBotHandler;
use RTippin\Messenger\Tests\Fixtures\FunBotHandler;
use RTippin\Messenger\Tests\Fixtures\FunBotPackage;
use RTippin\Messenger\Tests\Fixtures\SillyBotHandler;
use RTippin\Messenger\Tests\Fixtures\SillyBotPackage;
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
    }

    /** @test */
    public function messenger_bots_helper_same_instance_as_container()
    {
        $this->assertSame($this->bots, bots());
    }

    /** @test */
    public function messenger_bots_alias_same_instance_as_container()
    {
        $this->assertSame($this->bots, app('messenger-bots'));
    }

    /** @test */
    public function it_can_register_bot_handlers()
    {
        $handlers = [
            FunBotHandler::class,
            SillyBotHandler::class,
        ];

        $this->bots->registerHandlers($handlers);

        $this->assertSame($handlers, $this->bots->getHandlerClasses());
    }

    /** @test */
    public function it_can_register_packaged_bots()
    {
        $packages = [
            FunBotPackage::class,
            SillyBotPackage::class,
        ];

        $this->bots->registerPackagedBots($packages);

        $this->assertSame($packages, $this->bots->getPackagedBotClasses());
    }

    /** @test */
    public function it_can_get_unique_bot_handlers()
    {
        $this->bots->registerHandlers([
            FunBotHandler::class,
            SillyBotHandler::class,
        ]);

        $this->assertSame([SillyBotHandler::class], $this->bots->getUniqueHandlerClasses());
    }

    /** @test */
    public function it_can_get_bot_aliases_sorting_by_alias()
    {
        $handlers = [
            SillyBotHandler::class,
            FunBotHandler::class,
            BrokenBotHandler::class,
        ];
        $aliases = [
            'broken_bot',
            'fun_bot',
            'silly_bot',
        ];

        $this->bots->registerHandlers($handlers);

        $this->assertSame($aliases, $this->bots->getHandlerAliases());
    }

    /** @test */
    public function it_can_get_packaged_bot_aliases_sorting_by_alias()
    {
        $packages = [
            SillyBotPackage::class,
            FunBotPackage::class,
        ];
        $aliases = [
            'fun_package',
            'silly_package',
        ];

        $this->bots->registerPackagedBots($packages);

        $this->assertSame($aliases, $this->bots->getPackagedBotAliases());
    }

    /** @test */
    public function it_can_get_bot_match_methods()
    {
        $expected = [
            MessengerBots::MATCH_ANY,
            MessengerBots::MATCH_CONTAINS,
            MessengerBots::MATCH_CONTAINS_CASELESS,
            MessengerBots::MATCH_CONTAINS_ANY,
            MessengerBots::MATCH_CONTAINS_ANY_CASELESS,
            MessengerBots::MATCH_EXACT,
            MessengerBots::MATCH_EXACT_CASELESS,
            MessengerBots::MATCH_STARTS_WITH,
            MessengerBots::MATCH_STARTS_WITH_CASELESS,
        ];

        $this->assertSame($expected, $this->bots->getMatchMethods());
    }

    /** @test */
    public function it_can_get_bot_match_description()
    {
        $this->assertSame('The trigger must match the message exactly.', $this->bots->getMatchDescription(MessengerBots::MATCH_EXACT));
    }

    /** @test */
    public function it_returns_null_description_if_no_matching_match_method()
    {
        $this->assertNull($this->bots->getMatchDescription('exactt'));
        $this->assertNull($this->bots->getMatchDescription());
    }

    /** @test */
    public function it_can_get_all_handlers_sorting_by_name()
    {
        $this->bots->registerHandlers([
            SillyBotHandler::class,
            BrokenBotHandler::class,
            FunBotHandler::class,
        ]);

        $handlers = $this->bots->getHandlers();

        $this->assertSame(3, $handlers->count());
        $this->assertSame('broken_bot', $handlers[0]->alias);
        $this->assertSame('fun_bot', $handlers[1]->alias);
        $this->assertSame('silly_bot', $handlers[2]->alias);
    }

    /** @test */
    public function it_can_get_all_packaged_bots_sorting_by_name()
    {
        $this->bots->registerPackagedBots([
            SillyBotPackage::class,
            FunBotPackage::class,
        ]);

        $packages = $this->bots->getPackagedBots();

        $this->assertSame('fun_package', $packages[0]->alias);
        $this->assertSame('silly_package', $packages[1]->alias);
    }

    /** @test */
    public function it_can_get_single_handler()
    {
        $this->bots->registerHandlers([
            FunBotHandler::class,
            SillyBotHandler::class,
        ]);

        $this->assertSame('silly_bot', $this->bots->getHandler('silly_bot')->alias);
        $this->assertSame('silly_bot', $this->bots->getHandler(SillyBotHandler::class)->alias);
        $this->assertSame('fun_bot', $this->bots->getHandler('fun_bot')->alias);
        $this->assertSame('fun_bot', $this->bots->getHandler(FunBotHandler::class)->alias);
        $this->assertNull($this->bots->getHandler('unknown'));
    }

    /** @test */
    public function it_can_get_single_packaged_bot()
    {
        $this->bots->registerPackagedBots([
            SillyBotPackage::class,
            FunBotPackage::class,
        ]);

        $this->assertSame('fun_package', $this->bots->getPackagedBot('fun_package')->alias);
        $this->assertSame('fun_package', $this->bots->getPackagedBot(FunBotPackage::class)->alias);
        $this->assertSame('silly_package', $this->bots->getPackagedBot('silly_package')->alias);
        $this->assertSame('silly_package', $this->bots->getPackagedBot(SillyBotPackage::class)->alias);
        $this->assertNull($this->bots->getPackagedBot('unknown'));
    }

    /** @test */
    public function it_can_authorize_handler()
    {
        $this->bots->registerHandlers([
            FunBotHandler::class,
            SillyBotHandler::class,
        ]);
        $fun = $this->bots->getHandler(FunBotHandler::class);
        $silly = $this->bots->getHandler(SillyBotHandler::class);

        $this->assertTrue($this->bots->authorizeHandler($fun));
        $this->assertFalse($this->bots->authorizeHandler($silly));
    }

    /** @test */
    public function it_can_skip_authorizing_handler()
    {
        SillyBotHandler::$authorized = false;
        $this->bots->registerHandlers([
            SillyBotHandler::class,
        ]);
        $silly = $this->bots->getHandler(SillyBotHandler::class);

        $this->bots->shouldAuthorize(false);

        $this->assertTrue($this->bots->authorizeHandler($silly));
    }

    /** @test */
    public function it_can_get_authorized_handlers()
    {
        SillyBotHandler::$authorized = false;
        $this->bots->registerHandlers([
            FunBotHandler::class,
            SillyBotHandler::class,
        ]);

        $this->assertCount(2, $this->bots->getHandlers());
        $this->assertCount(1, $this->bots->getAuthorizedHandlers());
    }

    /** @test */
    public function it_can_authorize_packaged_bot()
    {
        SillyBotPackage::$authorized = false;
        $this->bots->registerPackagedBots([
            FunBotPackage::class,
            SillyBotPackage::class,
        ]);
        $fun = $this->bots->getPackagedBot(FunBotPackage::class);
        $silly = $this->bots->getPackagedBot(SillyBotPackage::class);

        $this->assertTrue($this->bots->authorizePackagedBot($fun));
        $this->assertFalse($this->bots->authorizePackagedBot($silly));
    }

    /** @test */
    public function it_can_skip_authorizing_packaged_bot()
    {
        SillyBotPackage::$authorized = false;
        $this->bots->registerPackagedBots([
            SillyBotPackage::class,
        ]);
        $silly = $this->bots->getPackagedBot(SillyBotPackage::class);

        $this->bots->shouldAuthorize(false);

        $this->assertTrue($this->bots->authorizePackagedBot($silly));
    }

    /** @test */
    public function it_can_get_authorized_packaged_bots()
    {
        SillyBotPackage::$authorized = false;
        $this->bots->registerPackagedBots([
            FunBotPackage::class,
            SillyBotPackage::class,
        ]);

        $this->assertCount(2, $this->bots->getPackagedBots());
        $this->assertCount(1, $this->bots->getAuthorizedPackagedBots());
    }

    /** @test */
    public function it_throws_exception_if_invalid_handler()
    {
        $handlers = [
            FunBotHandler::class,
            InvalidBotHandler::class,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The given handler { RTippin\Messenger\Tests\Messenger\InvalidBotHandler } must extend RTippin\Messenger\Support\BotActionHandler');

        $this->bots->registerHandlers($handlers);
    }

    /** @test */
    public function it_throws_exception_if_invalid_packaged_bot()
    {
        $packages = [
            FunBotPackage::class,
            InvalidPackagedBot::class,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The given package { RTippin\Messenger\Tests\Messenger\InvalidPackagedBot } must extend RTippin\Messenger\Support\PackagedBot');

        $this->bots->registerPackagedBots($packages);
    }

    /** @test */
    public function it_can_set_handlers_ignoring_duplicate()
    {
        $handlers = [
            FunBotHandler::class,
            SillyBotHandler::class,
        ];

        $this->bots->registerHandlers([FunBotHandler::class]);
        $this->bots->registerHandlers([SillyBotHandler::class]);
        $this->bots->registerHandlers([FunBotHandler::class]);

        $this->assertSame($handlers, $this->bots->getHandlerClasses());
    }

    /** @test */
    public function it_can_set_packaged_bots_ignoring_duplicate()
    {
        $packages = [
            FunBotPackage::class,
            SillyBotPackage::class,
        ];

        $this->bots->registerPackagedBots([FunBotPackage::class]);
        $this->bots->registerPackagedBots([SillyBotPackage::class]);
        $this->bots->registerPackagedBots([FunBotPackage::class]);

        $this->assertSame($packages, $this->bots->getPackagedBotClasses());
    }

    /** @test */
    public function it_can_reset_handlers()
    {
        $this->bots->registerHandlers([
            FunBotHandler::class,
            SillyBotHandler::class,
        ]);

        $this->assertCount(2, $this->bots->getHandlerClasses());

        $this->bots->registerHandlers([], true);

        $this->assertCount(0, $this->bots->getHandlerClasses());
    }

    /** @test */
    public function it_can_reset_packaged_bots()
    {
        $this->bots->registerPackagedBots([
            FunBotPackage::class,
            SillyBotPackage::class,
        ]);

        $this->assertCount(2, $this->bots->getPackagedBotClasses());

        $this->bots->registerPackagedBots([], true);

        $this->assertCount(0, $this->bots->getPackagedBotClasses());
    }

    /** @test */
    public function it_can_overwrite_existing_handlers()
    {
        $this->bots->registerHandlers([
            FunBotHandler::class,
            SillyBotHandler::class,
        ]);

        $this->assertCount(2, $this->bots->getHandlerClasses());

        $this->bots->registerHandlers([SillyBotHandler::class], true);

        $this->assertCount(1, $this->bots->getHandlerClasses());
    }

    /** @test */
    public function it_checks_if_valid_handler()
    {
        $this->bots->registerHandlers([
            FunBotHandler::class,
            SillyBotHandler::class,
        ]);

        $this->assertTrue($this->bots->isValidHandler(FunBotHandler::class));
        $this->assertTrue($this->bots->isValidHandler(SillyBotHandler::class));
        $this->assertFalse($this->bots->isValidHandler(InvalidBotHandler::class));
        $this->assertFalse($this->bots->isValidHandler(''));
        $this->assertFalse($this->bots->isValidHandler(null));
        $this->assertFalse($this->bots->isValidHandler());
    }

    /** @test */
    public function it_checks_if_valid_packaged_bot()
    {
        $this->bots->registerPackagedBots([
            FunBotPackage::class,
            SillyBotPackage::class,
        ]);

        $this->assertTrue($this->bots->isValidPackagedBot(FunBotPackage::class));
        $this->assertTrue($this->bots->isValidPackagedBot(SillyBotPackage::class));
        $this->assertFalse($this->bots->isValidPackagedBot(InvalidPackagedBot::class));
        $this->assertFalse($this->bots->isValidPackagedBot(''));
        $this->assertFalse($this->bots->isValidPackagedBot(null));
        $this->assertFalse($this->bots->isValidPackagedBot());
    }

    /** @test */
    public function it_checks_if_valid_handler_using_alias()
    {
        $this->bots->registerHandlers([
            FunBotHandler::class,
            SillyBotHandler::class,
        ]);

        $this->assertTrue($this->bots->isValidHandler('fun_bot'));
        $this->assertTrue($this->bots->isValidHandler('silly_bot'));
        $this->assertFalse($this->bots->isValidHandler('invalid'));
    }

    /** @test */
    public function it_checks_if_valid_packaged_bot_using_alias()
    {
        $this->bots->registerPackagedBots([
            FunBotPackage::class,
            SillyBotPackage::class,
        ]);

        $this->assertTrue($this->bots->isValidPackagedBot('fun_package'));
        $this->assertTrue($this->bots->isValidPackagedBot('silly_package'));
        $this->assertFalse($this->bots->isValidPackagedBot('invalid'));
    }

    /** @test */
    public function it_can_initialize_handler_using_class()
    {
        $this->bots->registerHandlers([FunBotHandler::class]);

        $this->assertInstanceOf(FunBotHandler::class, $this->bots->initializeHandler(FunBotHandler::class));
    }

    /** @test */
    public function it_can_initialize_packaged_bot_using_class()
    {
        $this->bots->registerPackagedBots([FunBotPackage::class]);

        $this->assertInstanceOf(FunBotPackage::class, $this->bots->initializePackagedBot(FunBotPackage::class));
    }

    /** @test */
    public function it_can_initialize_handler_using_alias()
    {
        $this->bots->registerHandlers([FunBotHandler::class]);

        $this->assertInstanceOf(FunBotHandler::class, $this->bots->initializeHandler('fun_bot'));
    }

    /** @test */
    public function it_can_initialize_packaged_bot_using_alias()
    {
        $this->bots->registerPackagedBots([FunBotPackage::class]);

        $this->assertInstanceOf(FunBotPackage::class, $this->bots->initializePackagedBot('fun_package'));
    }

    /** @test */
    public function it_returns_same_instance_if_initializing_already_active_handler()
    {
        $this->bots->registerHandlers([FunBotHandler::class]);
        $original = $this->bots->initializeHandler(FunBotHandler::class);

        $this->assertSame($original, $this->bots->initializeHandler(FunBotHandler::class));
    }

    /** @test */
    public function it_returns_new_instance_if_initializing_different_handler_when_another_set()
    {
        $this->bots->registerHandlers([
            FunBotHandler::class,
            SillyBotHandler::class,
        ]);
        $original = $this->bots->initializeHandler(FunBotHandler::class);

        $this->assertNotSame($original, $this->bots->initializeHandler(SillyBotHandler::class));
    }

    /** @test */
    public function it_throws_exception_if_initializing_invalid_handler()
    {
        $this->expectException(BotException::class);
        $this->expectExceptionMessage('Invalid bot handler.');

        $this->bots->registerHandlers([FunBotHandler::class]);
        $this->bots->initializeHandler(SillyBotHandler::class);
    }

    /** @test */
    public function it_throws_exception_if_initializing_invalid_packaged_bot()
    {
        $this->expectException(BotException::class);
        $this->expectExceptionMessage('Invalid bot package.');

        $this->bots->registerPackagedBots([FunBotPackage::class]);
        $this->bots->initializePackagedBot(SillyBotPackage::class);
    }

    /** @test */
    public function it_throws_exception_if_no_handler_supplied()
    {
        $this->expectException(BotException::class);
        $this->expectExceptionMessage('Invalid bot handler.');

        $this->bots->registerHandlers([FunBotHandler::class]);
        $this->bots->initializeHandler();
    }

    /** @test */
    public function it_can_access_initialized_handler()
    {
        $this->bots->registerHandlers([FunBotHandler::class]);
        $this->bots->initializeHandler(FunBotHandler::class);

        $this->assertInstanceOf(FunBotHandler::class, $this->bots->getActiveHandler());
        $this->assertTrue($this->bots->isActiveHandlerSet());
    }

    /** @test */
    public function it_returns_null_when_no_handler_initialized()
    {
        $this->bots->registerHandlers([FunBotHandler::class]);

        $this->assertNull($this->bots->getActiveHandler());
        $this->assertFalse($this->bots->isActiveHandlerSet());
    }

    /** @test */
    public function it_can_be_flushed()
    {
        $this->bots->registerHandlers([FunBotHandler::class]);
        $this->bots->initializeHandler(FunBotHandler::class);
        $this->bots->shouldAuthorize(false);

        $this->assertTrue($this->bots->isActiveHandlerSet());
        $this->assertFalse($this->bots->shouldAuthorize());

        $this->bots->flush();

        $this->assertFalse($this->bots->isActiveHandlerSet());
        $this->assertTrue($this->bots->shouldAuthorize());
    }
}

class InvalidBotHandler
{
    public function handle(): void
    {
        //
    }
}

class InvalidPackagedBot
{
    //
}
