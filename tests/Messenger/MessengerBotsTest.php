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
    public function it_can_get_bot_match_description()
    {
        $this->assertSame('The trigger must match the message exactly.', $this->bots->getMatchDescription('exact'));
    }

    /** @test */
    public function it_returns_null_description_if_no_matching_match_method()
    {
        $this->assertNull($this->bots->getMatchDescription('exactt'));
        $this->assertNull($this->bots->getMatchDescription());
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
                'authorize' => true,
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
            'authorize' => true,
        ];

        $this->bots->setHandlers($handlers);

        $this->assertSame($settings, $this->bots->getHandlerSettings('silly_bot'));
        $this->assertSame($settings, $this->bots->getHandlerSettings(TestBotTwoHandler::class));
        $this->assertNull($this->bots->getHandlerSettings('unknown'));
    }

    /** @test */
    public function it_can_get_authorized_handler_settings()
    {
        $this->bots->setHandlers([
            TestBotHandler::class,
            TestBotTwoHandler::class,
        ]);

        $this->assertCount(2, $this->bots->getHandlerSettings());
        $this->assertCount(1, $this->bots->getAuthorizedHandlers());
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
        $this->assertFalse($this->bots->isValidHandler());
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
        $this->assertFalse($this->bots->isValidHandler());
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
    public function it_returns_same_instance_if_initializing_already_active_handler()
    {
        $this->bots->setHandlers([TestBotHandler::class]);
        $original = $this->bots->initializeHandler(TestBotHandler::class);

        $this->assertSame($original, $this->bots->initializeHandler(TestBotHandler::class));
    }

    /** @test */
    public function it_returns_new_instance_if_initializing_different_handler_when_another_set()
    {
        $this->bots->setHandlers([
            TestBotHandler::class,
            TestBotTwoHandler::class,
        ]);
        $original = $this->bots->initializeHandler(TestBotHandler::class);

        $this->assertNotSame($original, $this->bots->initializeHandler(TestBotTwoHandler::class));
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
    public function it_throws_exception_if_no_handler_supplied()
    {
        $this->expectException(BotException::class);
        $this->expectExceptionMessage('Invalid bot handler.');

        $this->bots->setHandlers([TestBotHandler::class]);
        $this->bots->initializeHandler();
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
    public function it_fails_validating_handler_via_alias()
    {
        $this->bots->setHandlers([TestBotHandler::class]);

        try {
            $this->bots->resolveHandlerData(['handler' => 'silly_bot']);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('handler', $e->errors());
        }
    }

    /** @test */
    public function it_fails_initializing_given_invalid_handler()
    {
        $this->bots->setHandlers([TestBotHandler::class]);

        $this->expectException(BotException::class);
        $this->expectExceptionMessage('Invalid bot handler.');

        $this->bots->resolveHandlerData(['handler' => 'fun_bot'], TestBotTwoHandler::class);
    }

    /** @test */
    public function it_initializes_handler_after_validating_valid_alias()
    {
        $this->bots->setHandlers([TestBotHandler::class]);

        try {
            $this->bots->resolveHandlerData([
                'handler' => 'fun_bot',
            ]);
        } catch (ValidationException $e) {
            $this->assertInstanceOf(TestBotHandler::class, $this->bots->getActiveHandler());
            $this->assertTrue($this->bots->isActiveHandlerSet());
        }
    }

    /** @test */
    public function it_returns_final_resolved_data_using_alias_in_data()
    {
        $this->bots->setHandlers([TestBotTwoHandler::class]);
        $expects = [
            'handler' => TestBotTwoHandler::class,
            'unique' => true,
            'authorize' => true,
            'name' => 'Silly Bot',
            'match' => 'exact',
            'triggers' => 'test',
            'admin_only' => true,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => null,
        ];
        $results = $this->bots->resolveHandlerData([
            'handler' => 'silly_bot',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => true,
            'enabled' => true,
            'triggers' => ['test'],
        ]);

        $this->assertSame($expects, $results);
    }

    /** @test */
    public function it_returns_final_resolved_data_using_handler_class()
    {
        $this->bots->setHandlers([TestBotTwoHandler::class]);
        $expects = [
            'handler' => TestBotTwoHandler::class,
            'unique' => true,
            'authorize' => true,
            'name' => 'Silly Bot',
            'match' => 'exact',
            'triggers' => 'test',
            'admin_only' => true,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => null,
        ];
        $results = $this->bots->resolveHandlerData([
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => true,
            'enabled' => true,
            'triggers' => ['test'],
        ], TestBotTwoHandler::class);

        $this->assertSame($expects, $results);
    }

    /** @test */
    public function it_returns_final_resolved_data_using_handler_alias()
    {
        $this->bots->setHandlers([TestBotTwoHandler::class]);
        $expects = [
            'handler' => TestBotTwoHandler::class,
            'unique' => true,
            'authorize' => true,
            'name' => 'Silly Bot',
            'match' => 'exact',
            'triggers' => 'test',
            'admin_only' => true,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => null,
        ];
        $results = $this->bots->resolveHandlerData([
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => true,
            'enabled' => true,
            'triggers' => ['test'],
        ], 'silly_bot');

        $this->assertSame($expects, $results);
    }

    /** @test */
    public function it_ignores_properties_the_handler_overwrites()
    {
        $this->bots->setHandlers([TestBotHandler::class]);
        $expects = [
            'handler' => TestBotHandler::class,
            'unique' => false,
            'authorize' => false,
            'name' => 'Fun Bot',
            'match' => 'exact:caseless', //overwritten
            'triggers' => '!test|!more', //overwritten
            'admin_only' => true,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => '{"test":["test"]}',
        ];
        $results = $this->bots->resolveHandlerData([
            'handler' => 'fun_bot',
            'match' => 'contains',
            'cooldown' => 0,
            'admin_only' => true,
            'enabled' => true,
            'triggers' => ['!some', '!more'],
            'test' => ['test'],
        ]);

        $this->assertSame($expects, $results);
    }

    /** @test */
    public function overwritten_properties_can_be_omitted()
    {
        $this->bots->setHandlers([TestBotHandler::class]);
        $expects = [
            'handler' => TestBotHandler::class,
            'unique' => false,
            'authorize' => false,
            'name' => 'Fun Bot',
            'match' => 'exact:caseless', //overwritten
            'triggers' => '!test|!more', //overwritten
            'admin_only' => true,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => '{"test":["test"]}',
        ];
        $results = $this->bots->resolveHandlerData([
            'handler' => 'fun_bot',
            'cooldown' => 0,
            'admin_only' => true,
            'enabled' => true,
            'test' => ['test'],
        ]);

        $this->assertSame($expects, $results);
    }

    /** @test */
    public function it_formats_payload_using_handler_custom_rules_only()
    {
        $this->bots->setHandlers([TestBotHandler::class]);
        $expects = '{"test":{"test":"fun","more":"yes","ok":"dokie"},"special":true}';
        $results = $this->bots->resolveHandlerData([
            'handler' => 'fun_bot',
            'cooldown' => 0,
            'admin_only' => true,
            'enabled' => true,
            'test' => [
                'test' => 'fun',
                'more' => 'yes',
                'ok' => 'dokie',
            ],
            'special' => true,
        ]);

        $this->assertSame($expects, $results['payload']);
    }

    /**
     * @test
     * @param $match
     * @param $cooldown
     * @param $admin
     * @param $enabled
     * @param $triggers
     * @dataProvider baseRulesetFailsValidation
     */
    public function it_fails_validating_base_ruleset($match, $cooldown, $admin, $enabled, $triggers)
    {
        $this->bots->setHandlers([TestBotTwoHandler::class]);

        try {
            $this->bots->resolveHandlerData([
                'handler' => 'silly_bot',
                'match' => $match,
                'cooldown' => $cooldown,
                'admin_only' => $admin,
                'enabled' => $enabled,
                'triggers' => $triggers,
            ]);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('match', $e->errors());
            $this->assertArrayHasKey('cooldown', $e->errors());
            $this->assertArrayHasKey('admin_only', $e->errors());
            $this->assertArrayHasKey('enabled', $e->errors());
            $this->assertArrayHasKey('triggers', $e->errors());
        }
    }

    /**
     * @test
     * @param $triggers
     * @param $errorKeys
     * @dataProvider triggersFailValidation
     */
    public function it_fails_validating_triggers($triggers, $errorKeys)
    {
        $this->bots->setHandlers([TestBotTwoHandler::class]);

        try {
            $this->bots->resolveHandlerData([
                'handler' => 'silly_bot',
                'match' => 'exact',
                'cooldown' => 0,
                'admin_only' => false,
                'enabled' => true,
                'triggers' => $triggers,
            ]);
        } catch (ValidationException $e) {
            foreach ($errorKeys as $error) {
                $this->assertArrayHasKey($error, $e->errors());
            }
        }
    }

    /**
     * @test
     * @param $matches
     * @dataProvider passesValidatingMatches
     */
    public function it_passes_validating_matches($matches)
    {
        $this->bots->setHandlers([TestBotTwoHandler::class]);
        $this->bots->resolveHandlerData([
            'handler' => 'silly_bot',
            'match' => $matches,
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => ['test'],
        ]);

        $this->assertTrue($this->bots->isActiveHandlerSet());
    }

    /**
     * @test
     * @param $cooldown
     * @dataProvider passesValidatingCooldown
     */
    public function it_passes_validating_cooldown($cooldown)
    {
        $this->bots->setHandlers([TestBotTwoHandler::class]);
        $this->bots->resolveHandlerData([
            'handler' => 'silly_bot',
            'match' => 'exact',
            'cooldown' => $cooldown,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => ['test'],
        ]);

        $this->assertTrue($this->bots->isActiveHandlerSet());
    }

    /**
     * @test
     * @param $extra
     * @param $errorKeys
     * @dataProvider handlerRulesFailValidation
     */
    public function it_fails_validating_handler_rules($extra, $errorKeys)
    {
        $this->bots->setHandlers([TestBotHandler::class]);

        try {
            $this->bots->resolveHandlerData([
                'handler' => 'fun_bot',
                'cooldown' => 0,
                'admin_only' => false,
                'enabled' => true,
                'test' => $extra,
            ]);
        } catch (ValidationException $e) {
            foreach ($errorKeys as $error) {
                $this->assertArrayHasKey($error, $e->errors());
            }
        }
    }

    /**
     * @test
     * @param $triggers
     * @param $result
     * @dataProvider triggersGetFormatted
     */
    public function it_formats_triggers($triggers, $result)
    {
        $this->bots->setHandlers([TestBotTwoHandler::class]);
        $results = $this->bots->resolveHandlerData([
            'handler' => 'silly_bot',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => $triggers,
        ]);

        $this->assertSame($result, $results['triggers']);
    }

    public function baseRulesetFailsValidation(): array
    {
        return [
            'Attempt 1' => [null, null, null, null, null],
            'Attempt 2' => [true, 'test', 'test', 'test', true],
            'Attempt 3' => [true, -1, 'test', 'test', true],
            'Attempt 4' => ['unknown', 999, null, null, 'test'],
            'Attempt 5' => ['exact:lol', 999, null, null, []],
            'Attempt 6' => ['exact:something', 1500, 'test', 'test', false],
        ];
    }

    public function triggersFailValidation(): array
    {
        return [
            'Cannot be empty array' => [[[]], ['triggers.0']],
            'Cannot be null or integer' => [[null, 1], ['triggers.0', 'triggers.1']],
            'Cannot be integer or nested array' => [[1, 1, []], ['triggers.0', 'triggers.1', 'triggers.2']],
            'Second value fails' => [['test', 1], ['triggers.1']],
            'Cannot be boolean true' => [['test', true], ['triggers.1']],
            'Cannot be boolean false' => [['test', false], ['triggers.1']],
            'Cannot be floats or negative or integers' => [[1, 1.1, -1], ['triggers.0', 'triggers.1', 'triggers.2']],
            'Cannot be only comma or pipe, end triggers will be empty' => [[',', '|', ', |'], ['triggers']],
        ];
    }

    public function passesValidatingMatches(): array
    {
        return [
            'contains' => ['contains'],
            'contains caseless' => ['contains:caseless'],
            'contains any' => ['contains:any'],
            'contains any caseless' => ['contains:any:caseless'],
            'exact' => ['exact'],
            'exact caseless' => ['exact:caseless'],
            'starts with' => ['starts:with'],
            'starts with caseless' => ['starts:with:caseless'],
        ];
    }

    public function passesValidatingCooldown(): array
    {
        return [
            'Can be lowest value' => [0],
            'Can be random value' => [55],
            'Can be almost highest value' => [899],
            'Can be highest value' => [900],
            'Can be 1' => [1],
        ];
    }

    public function handlerRulesFailValidation(): array
    {
        return [
            'Attempt 1' => [null, ['test']],
            'Attempt 2' => [[null], ['test.0']],
            'Attempt 3' => [[0, 2], ['test.0', 'test.1']],
            'Attempt 4' => [['test', false, null], ['test.1', 'test.2']],
        ];
    }

    public function triggersGetFormatted(): array
    {
        return [
            'Single trigger' => [['test'], 'test'],
            'Multiple triggers' => [['test', 'another'], 'test|another'],
            'Omits duplicates' => [['test', '1', '2', 'test', '3', '1'], 'test|1|2|3'],
            'Can separate via commas' => [['test, 1,2, 3', '4'], 'test|1|2|3|4'],
            'Can separate via pipe' => [['test| 1|2| 3', '4'], 'test|1|2|3|4'],
            'Can separate via comma and pipe' => [['test, 1|2| 3', '4,5'], 'test|1|2|3|4|5'],
            'Multiple filters combined' => [['test, 1|2| 3', '4,5', '1|2', ',|', '6'], 'test|1|2|3|4|5|6'],
            'Removes empty values' => [['test', '1', '2', ',', '|', '|3|3,||'], 'test|1|2|3'],
        ];
    }
}

class InvalidBotHandler
{
    public function handle(): void
    {
        //
    }
}
