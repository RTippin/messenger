<?php

namespace RTippin\Messenger\Tests\Messenger;

use InvalidArgumentException;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Facades\MessengerBots as BotsFacade;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\BotActionHandler;
use RTippin\Messenger\Tests\Fixtures\BrokenBotHandler;
use RTippin\Messenger\Tests\Fixtures\FunBotHandler;
use RTippin\Messenger\Tests\Fixtures\FunBotPackage;
use RTippin\Messenger\Tests\Fixtures\SillyBotHandler;
use RTippin\Messenger\Tests\Fixtures\SillyBotPackage;
use RTippin\Messenger\Tests\Fixtures\UserModel;
use RTippin\Messenger\Tests\MessengerTestCase;

class MessengerBotsTest extends MessengerTestCase
{
    private MessengerBots $bots;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bots = app(MessengerBots::class);
    }

    protected function tearDown(): void
    {
        BotActionHandler::isTesting(false);

        parent::tearDown();
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
    public function it_shows_handlers_not_testing_by_default()
    {
        $this->assertFalse(FunBotHandler::isTesting());
        $this->assertFalse(SillyBotHandler::isTesting());
    }

    /** @test */
    public function it_can_set_handlers_to_testing()
    {
        BotActionHandler::isTesting(true);

        $this->assertTrue(FunBotHandler::isTesting());
        $this->assertTrue(SillyBotHandler::isTesting());
    }

    /** @test */
    public function it_can_set_bot_handlers()
    {
        $handlers = [
            FunBotHandler::class,
            SillyBotHandler::class,
        ];

        $this->bots->registerHandlers($handlers);

        $this->assertSame($handlers, $this->bots->getHandlerClasses());
    }

    /** @test */
    public function it_can_set_packaged_bots()
    {
        $packages = [
            FunBotPackage::class,
            SillyBotPackage::class,
        ];

        $this->bots->registerPackagedBots($packages);

        $this->assertSame($packages, $this->bots->getPackagedBotClasses());
    }

    /** @test */
    public function it_tests_bot_package()
    {
        $packages = [
            FunBotPackage::class,
        ];

        $this->bots->registerPackagedBots($packages);

        dump($this->bots->getPackagedBotsDTO()->first());

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

        $this->assertSame($aliases, $this->bots->getAliases());
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
    public function it_can_get_all_handler_dtos_sorting_by_name()
    {
        $handlers = [
            SillyBotHandler::class,
            BrokenBotHandler::class,
            FunBotHandler::class,
        ];
        $dtos = [
            [
                'alias' => 'broken_bot',
                'description' => 'This is a broken bot.',
                'name' => 'Broken Bot',
                'unique' => true,
                'authorize' => false,
                'triggers' => null,
                'match' => null,
            ],
            [
                'alias' => 'fun_bot',
                'description' => 'This is a fun bot.',
                'name' => 'Fun Bot',
                'unique' => false,
                'authorize' => false,
                'triggers' => ['!test', '!more'],
                'match' => MessengerBots::MATCH_EXACT_CASELESS,
            ],
            [
                'alias' => 'silly_bot',
                'description' => 'This is a silly bot.',
                'name' => 'Silly Bot',
                'unique' => true,
                'authorize' => true,
                'triggers' => null,
                'match' => null,
            ],
        ];

        $this->bots->registerHandlers($handlers);

        $this->assertSame($dtos, $this->bots->getHandlersDTO()->toArray());
    }

    /** @test */
    public function it_can_get_single_handler_dto()
    {
        $handlers = [
            FunBotHandler::class,
            SillyBotHandler::class,
        ];
        $dto = [
            'alias' => 'silly_bot',
            'description' => 'This is a silly bot.',
            'name' => 'Silly Bot',
            'unique' => true,
            'authorize' => true,
            'triggers' => null,
            'match' => null,
        ];

        $this->bots->registerHandlers($handlers);

        $this->assertSame($dto, $this->bots->getHandlersDTO('silly_bot')->toArray());
        $this->assertSame($dto, $this->bots->getHandlersDTO(SillyBotHandler::class)->toArray());
        $this->assertNull($this->bots->getHandlersDTO('unknown'));
    }

    /** @test */
    public function it_removes_trigger_overrides_if_match_override_is_match_any()
    {
        SillyBotHandler::$triggers = ['one', 'two'];
        SillyBotHandler::$match = MessengerBots::MATCH_ANY;
        $dto = [
            'alias' => 'silly_bot',
            'description' => 'This is a silly bot.',
            'name' => 'Silly Bot',
            'unique' => true,
            'authorize' => true,
            'triggers' => null,
            'match' => MessengerBots::MATCH_ANY,
        ];

        $this->bots->registerHandlers([SillyBotHandler::class]);

        $this->assertSame($dto, $this->bots->getHandlersDTO(SillyBotHandler::class)->toArray());
    }

    /** @test */
    public function it_can_get_authorized_handlers_dto()
    {
        $this->bots->registerHandlers([
            FunBotHandler::class,
            SillyBotHandler::class,
        ]);

        $this->assertCount(2, $this->bots->getHandlersDTO());
        $this->assertCount(1, $this->bots->getAuthorizedHandlers());
    }

    /** @test */
    public function it_throws_exception_if_invalid_handler()
    {
        $actions = [
            FunBotHandler::class,
            InvalidBotHandler::class,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The given handler { RTippin\Messenger\Tests\Messenger\InvalidBotHandler } must extend RTippin\Messenger\Support\BotActionHandler');

        $this->bots->registerHandlers($actions);
    }

    /** @test */
    public function it_can_set_handlers_adding_ones_to_existing_and_ignoring_duplicate()
    {
        $actions = [
            FunBotHandler::class,
            SillyBotHandler::class,
        ];

        $this->bots->registerHandlers([FunBotHandler::class]);
        $this->bots->registerHandlers([SillyBotHandler::class]);
        $this->bots->registerHandlers([FunBotHandler::class]);

        $this->assertSame($actions, $this->bots->getHandlerClasses());
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
        $handlers = [
            FunBotHandler::class,
            SillyBotHandler::class,
        ];

        $this->bots->registerHandlers($handlers);

        $this->assertTrue($this->bots->isValidHandler(FunBotHandler::class));
        $this->assertTrue($this->bots->isValidHandler(SillyBotHandler::class));
        $this->assertFalse($this->bots->isValidHandler(InvalidBotHandler::class));
        $this->assertFalse($this->bots->isValidHandler(''));
        $this->assertFalse($this->bots->isValidHandler(null));
        $this->assertFalse($this->bots->isValidHandler());
    }

    /** @test */
    public function it_checks_if_valid_handler_using_alias()
    {
        $handlers = [
            FunBotHandler::class,
            SillyBotHandler::class,
        ];

        $this->bots->registerHandlers($handlers);

        $this->assertTrue($this->bots->isValidHandler('fun_bot'));
        $this->assertTrue($this->bots->isValidHandler('silly_bot'));
        $this->assertFalse($this->bots->isValidHandler('invalid'));
        $this->assertFalse($this->bots->isValidHandler());
    }

    /** @test */
    public function it_can_initialize_bot_using_class()
    {
        $this->bots->registerHandlers([FunBotHandler::class]);

        $this->assertInstanceOf(FunBotHandler::class, $this->bots->initializeHandler(FunBotHandler::class));
    }

    /** @test */
    public function it_can_initialize_bot_using_alias()
    {
        $this->bots->registerHandlers([FunBotHandler::class]);

        $this->assertInstanceOf(FunBotHandler::class, $this->bots->initializeHandler('fun_bot'));
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
    public function it_throws_exception_if_invalid_bot()
    {
        $this->expectException(BotException::class);
        $this->expectExceptionMessage('Invalid bot handler.');

        $this->bots->registerHandlers([FunBotHandler::class]);
        $this->bots->initializeHandler(SillyBotHandler::class);
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
    public function it_can_access_initialized_bot()
    {
        $this->bots->registerHandlers([FunBotHandler::class]);
        $this->bots->initializeHandler(FunBotHandler::class);

        $this->assertInstanceOf(FunBotHandler::class, $this->bots->getActiveHandler());
        $this->assertTrue($this->bots->isActiveHandlerSet());
    }

    /** @test */
    public function it_returns_null_when_no_bot_initialized()
    {
        $this->bots->registerHandlers([FunBotHandler::class]);

        $this->assertNull($this->bots->getActiveHandler());
        $this->assertFalse($this->bots->isActiveHandlerSet());
    }

    /** @test */
    public function it_can_flush_active_handler()
    {
        $this->bots->registerHandlers([FunBotHandler::class]);
        $this->bots->initializeHandler(FunBotHandler::class);

        $this->assertTrue($this->bots->isActiveHandlerSet());

        $this->bots->flush();

        $this->assertFalse($this->bots->isActiveHandlerSet());
    }

    /** @test */
    public function it_can_get_actions_payload()
    {
        $user = UserModel::factory()->make();
        $thread = Thread::factory()->group()->make();
        $message = Message::factory()->for($thread)->make();
        $bot = Bot::factory()->for($thread)->owner($user)->make();
        $action = BotAction::factory()
            ->for($bot)
            ->owner($user)
            ->payload('{"test":{"test":"fun","more":"yes","ok":"dokie"},"special":true}')
            ->make();
        $emptyAction = BotAction::factory()
            ->for($bot)
            ->owner($user)
            ->make();

        $emptyHandler = (new FunBotHandler)->setDataForHandler($thread, $emptyAction, $message);
        $handler = (new FunBotHandler)->setDataForHandler($thread, $action, $message);

        $this->assertNull($emptyHandler->getPayload());
        $this->assertNull($emptyHandler->getPayload('unknown'));
        $this->assertTrue($handler->getPayload('special'));
        $this->assertSame('fun', $handler->getPayload('test')['test']);
        $this->assertSame([
            'test' => [
                'test' => 'fun',
                'more' => 'yes',
                'ok' => 'dokie',
            ],
            'special' => true,
        ], $handler->getPayload());
    }

    /** @test */
    public function it_can_get_actions_parsed_message()
    {
        $user = UserModel::factory()->make();
        $thread = Thread::factory()->group()->make();
        $message = Message::factory()->for($thread)->body('!command Do Something Fun')->make();
        $bot = Bot::factory()->for($thread)->owner($user)->make();
        $action = BotAction::factory()->for($bot)->owner($user)->make();

        $handler = (new FunBotHandler)->setDataForHandler($thread, $action, $message, '!command');
        $emptyHandler = (new FunBotHandler)->setDataForHandler($thread, $action, $message, '!command Do Something Fun');

        $this->assertSame('Do Something Fun', $handler->getParsedMessage());
        $this->assertSame('do something fun', $handler->getParsedMessage(true));
        $this->assertSame(['Do', 'Something', 'Fun'], $handler->getParsedWords());
        $this->assertSame(['do', 'something', 'fun'], $handler->getParsedWords(true));
        $this->assertNull($emptyHandler->getParsedMessage());
        $this->assertNull($emptyHandler->getParsedMessage(true));
        $this->assertNull($emptyHandler->getParsedWords());
        $this->assertNull($emptyHandler->getParsedWords(true));
    }

    /** @test */
    public function it_can_get_actions_parsed_message_when_no_trigger()
    {
        $user = UserModel::factory()->make();
        $thread = Thread::factory()->group()->make();
        $message = Message::factory()->for($thread)->body('!command Do Something Fun')->make();
        $bot = Bot::factory()->for($thread)->owner($user)->make();
        $action = BotAction::factory()->for($bot)->owner($user)->make();

        $handler = (new FunBotHandler)->setDataForHandler($thread, $action, $message);

        $this->assertSame('!command Do Something Fun', $handler->getParsedMessage());
        $this->assertSame('!command do something fun', $handler->getParsedMessage(true));
        $this->assertSame(['!command', 'Do', 'Something', 'Fun'], $handler->getParsedWords());
        $this->assertSame(['!command', 'do', 'something', 'fun'], $handler->getParsedWords(true));
    }
}

class InvalidBotHandler
{
    public function handle(): void
    {
        //
    }
}
