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
    public function it_registers_bot_handlers_defined_in_a_packaged_bot()
    {
        $this->assertFalse($this->bots->isValidHandler(FunBotHandler::class));
        $this->assertFalse($this->bots->isValidHandler(SillyBotHandler::class));
        $this->assertFalse($this->bots->isValidHandler(BrokenBotHandler::class));

        $this->bots->registerPackagedBots([FunBotPackage::class]);

        $this->assertTrue($this->bots->isValidHandler(FunBotHandler::class));
        $this->assertTrue($this->bots->isValidHandler(SillyBotHandler::class));
        $this->assertTrue($this->bots->isValidHandler(BrokenBotHandler::class));
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
        $handlers = [
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

        $this->assertSame($handlers, $this->bots->getHandlers()->toArray());
    }

    /** @test */
    public function it_can_get_all_packaged_bots_sorting_by_name()
    {
        SillyBotHandler::$authorized = true;
        $this->bots->registerPackagedBots([
            SillyBotPackage::class,
            FunBotPackage::class,
        ]);
        $broken = $this->bots->getHandlers(BrokenBotHandler::class)->toArray();
        $fun = $this->bots->getHandlers(FunBotHandler::class)->toArray();
        $silly = $this->bots->getHandlers(SillyBotHandler::class)->toArray();
        $packages = [
            [
                'alias' => 'fun_package',
                'name' => 'Fun Package',
                'description' => 'Fun package description.',
                'avatar' => [
                    'sm' => '/messenger/assets/bot-package/sm/fun_package/avatar.png',
                    'md' => '/messenger/assets/bot-package/md/fun_package/avatar.png',
                    'lg' => '/messenger/assets/bot-package/lg/fun_package/avatar.png',
                ],
                'installs' => [
                    $broken,
                    $fun,
                    $silly,
                ],
            ],
            [
                'alias' => 'silly_package',
                'name' => 'Silly Package',
                'description' => 'Silly package description.',
                'avatar' => [
                    'sm' => '/messenger/assets/bot-package/sm/silly_package/avatar.png',
                    'md' => '/messenger/assets/bot-package/md/silly_package/avatar.png',
                    'lg' => '/messenger/assets/bot-package/lg/silly_package/avatar.png',
                ],
                'installs' => [
                    $fun,
                    $silly,
                ],
            ],
        ];

        $this->assertSame($packages, $this->bots->getPackagedBots()->toArray());
    }

    /** @test */
    public function it_can_get_single_handler()
    {
        $this->bots->registerHandlers([
            FunBotHandler::class,
            SillyBotHandler::class,
        ]);
        $fun = [
            'alias' => 'fun_bot',
            'description' => 'This is a fun bot.',
            'name' => 'Fun Bot',
            'unique' => false,
            'authorize' => false,
            'triggers' => ['!test', '!more'],
            'match' => MessengerBots::MATCH_EXACT_CASELESS,
        ];
        $silly = [
            'alias' => 'silly_bot',
            'description' => 'This is a silly bot.',
            'name' => 'Silly Bot',
            'unique' => true,
            'authorize' => true,
            'triggers' => null,
            'match' => null,
        ];

        $this->assertSame($silly, $this->bots->getHandlers('silly_bot')->toArray());
        $this->assertSame($silly, $this->bots->getHandlers(SillyBotHandler::class)->toArray());
        $this->assertSame($fun, $this->bots->getHandlers('fun_bot')->toArray());
        $this->assertSame($fun, $this->bots->getHandlers(FunBotHandler::class)->toArray());
        $this->assertNull($this->bots->getHandlers('unknown'));
    }

    /** @test */
    public function it_can_get_single_packaged_bot()
    {
        SillyBotHandler::$authorized = true;
        $this->bots->registerPackagedBots([
            SillyBotPackage::class,
            FunBotPackage::class,
        ]);
        $broken = $this->bots->getHandlers(BrokenBotHandler::class)->toArray();
        $fun = $this->bots->getHandlers(FunBotHandler::class)->toArray();
        $silly = $this->bots->getHandlers(SillyBotHandler::class)->toArray();
        $funPackage = [
            'alias' => 'fun_package',
            'name' => 'Fun Package',
            'description' => 'Fun package description.',
            'avatar' => [
                'sm' => '/messenger/assets/bot-package/sm/fun_package/avatar.png',
                'md' => '/messenger/assets/bot-package/md/fun_package/avatar.png',
                'lg' => '/messenger/assets/bot-package/lg/fun_package/avatar.png',
            ],
            'installs' => [
                $broken,
                $fun,
                $silly,
            ],
        ];
        $sillyPackage = [
            'alias' => 'silly_package',
            'name' => 'Silly Package',
            'description' => 'Silly package description.',
            'avatar' => [
                'sm' => '/messenger/assets/bot-package/sm/silly_package/avatar.png',
                'md' => '/messenger/assets/bot-package/md/silly_package/avatar.png',
                'lg' => '/messenger/assets/bot-package/lg/silly_package/avatar.png',
            ],
            'installs' => [
                $fun,
                $silly,
            ],
        ];

        $this->assertSame($funPackage, $this->bots->getPackagedBots('fun_package')->toArray());
        $this->assertSame($funPackage, $this->bots->getPackagedBots(FunBotPackage::class)->toArray());
        $this->assertSame($sillyPackage, $this->bots->getPackagedBots('silly_package')->toArray());
        $this->assertSame($sillyPackage, $this->bots->getPackagedBots(SillyBotPackage::class)->toArray());
        $this->assertNull($this->bots->getPackagedBots('unknown'));
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

        $this->assertSame($dto, $this->bots->getHandlers(SillyBotHandler::class)->toArray());
    }

    /** @test */
    public function it_can_get_authorized_handlers()
    {
        $this->bots->registerHandlers([
            FunBotHandler::class,
            SillyBotHandler::class,
        ]);

        $this->assertCount(2, $this->bots->getHandlers());
        $this->assertCount(1, $this->bots->getAuthorizedHandlers());
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
    public function it_filters_authorized_handlers_when_calling_to_array_on_packaged_bot_collection()
    {
        SillyBotHandler::$authorized = false;
        $this->bots->registerPackagedBots([
            FunBotPackage::class,
        ]);
        $broken = $this->bots->getHandlers(BrokenBotHandler::class)->toArray();
        $fun = $this->bots->getHandlers(FunBotHandler::class)->toArray();
        $funPackage = [
            'alias' => 'fun_package',
            'name' => 'Fun Package',
            'description' => 'Fun package description.',
            'avatar' => [
                'sm' => '/messenger/assets/bot-package/sm/fun_package/avatar.png',
                'md' => '/messenger/assets/bot-package/md/fun_package/avatar.png',
                'lg' => '/messenger/assets/bot-package/lg/fun_package/avatar.png',
            ],
            'installs' => [
                $broken,
                $fun,
            ],
        ];

        $this->assertSame($funPackage, $this->bots->getPackagedBots(FunBotPackage::class)->toArray());
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

class InvalidPackagedBot
{
    //
}
