<?php

namespace RTippin\Messenger\Tests\Services;

use Illuminate\Validation\ValidationException;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Services\BotHandlerResolverService;
use RTippin\Messenger\Support\BotActionHandler;
use RTippin\Messenger\Tests\Fixtures\FunBotHandler;
use RTippin\Messenger\Tests\Fixtures\SillyBotHandler;
use RTippin\Messenger\Tests\MessengerTestCase;

class BotHandlerResolverServiceTest extends MessengerTestCase
{
    private MessengerBots $bots;
    private BotHandlerResolverService $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bots = app(MessengerBots::class);
        $this->resolver = app(BotHandlerResolverService::class);
    }

    protected function tearDown(): void
    {
        BotActionHandler::isTesting(false);

        parent::tearDown();
    }

    /** @test */
    public function it_fails_validating_handler_via_alias()
    {
        $this->bots->registerHandlers([FunBotHandler::class]);

        try {
            $this->resolver->resolve(['handler' => 'silly_bot']);
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('handler', $e->errors());
        }
    }

    /** @test */
    public function it_fails_initializing_given_invalid_handler()
    {
        $this->bots->registerHandlers([FunBotHandler::class]);

        $this->expectException(BotException::class);
        $this->expectExceptionMessage('Invalid bot handler.');

        $this->resolver->resolve(['handler' => 'fun_bot'], SillyBotHandler::class);
    }

    /** @test */
    public function it_initializes_handler_after_validating_valid_alias()
    {
        $this->bots->registerHandlers([FunBotHandler::class]);

        try {
            $this->resolver->resolve([
                'handler' => 'fun_bot',
            ]);
        } catch (ValidationException $e) {
            $this->assertInstanceOf(FunBotHandler::class, $this->bots->getActiveHandler());
            $this->assertTrue($this->bots->isActiveHandlerSet());
        }
    }

    /** @test */
    public function it_returns_final_resolved_dto_using_alias_in_data()
    {
        $this->bots->registerHandlers([SillyBotHandler::class]);
        $expects = [
            'handler' => SillyBotHandler::getDTO()->toArray(),
            'match' => MessengerBots::MATCH_EXACT,
            'triggers' => 'test',
            'admin_only' => true,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => null,
        ];
        $results = $this->resolver->resolve([
            'handler' => 'silly_bot',
            'match' => MessengerBots::MATCH_EXACT,
            'cooldown' => 0,
            'admin_only' => true,
            'enabled' => true,
            'triggers' => ['test'],
        ]);

        $this->assertSame($expects, $results->toArray());
    }

    /** @test */
    public function it_returns_final_resolved_dto_using_handler_class_override()
    {
        $this->bots->registerHandlers([SillyBotHandler::class]);
        $expects = [
            'handler' => SillyBotHandler::getDTO()->toArray(),
            'match' => MessengerBots::MATCH_EXACT,
            'triggers' => 'test',
            'admin_only' => true,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => null,
        ];
        $results = $this->resolver->resolve([
            'match' => MessengerBots::MATCH_EXACT,
            'cooldown' => 0,
            'admin_only' => true,
            'enabled' => true,
            'triggers' => ['test'],
        ], SillyBotHandler::class);

        $this->assertSame($expects, $results->toArray());
    }

    /** @test */
    public function it_returns_final_resolved_dto_using_handler_alias_override()
    {
        $this->bots->registerHandlers([SillyBotHandler::class]);
        $expects = [
            'handler' => SillyBotHandler::getDTO()->toArray(),
            'match' => MessengerBots::MATCH_EXACT,
            'triggers' => 'test',
            'admin_only' => true,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => null,
        ];
        $results = $this->resolver->resolve([
            'match' => MessengerBots::MATCH_EXACT,
            'cooldown' => 0,
            'admin_only' => true,
            'enabled' => true,
            'triggers' => ['test'],
        ], 'silly_bot');

        $this->assertSame($expects, $results->toArray());
    }

    /** @test */
    public function it_ignores_properties_the_handler_overwrites()
    {
        $this->bots->registerHandlers([FunBotHandler::class]);
        $expects = [
            'handler' => FunBotHandler::getDTO()->toArray(),
            'match' => MessengerBots::MATCH_EXACT_CASELESS, //overwritten
            'triggers' => '!test|!more', //overwritten
            'admin_only' => true,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => '{"test":["test"]}',
        ];

        $results = $this->resolver->resolve([
            'handler' => 'fun_bot',
            'match' => MessengerBots::MATCH_CONTAINS,
            'cooldown' => 0,
            'admin_only' => true,
            'enabled' => true,
            'triggers' => ['!some', '!more'],
            'test' => ['test'],
        ]);

        $this->assertSame($expects, $results->toArray());
    }

    /** @test */
    public function it_ignores_triggers_if_using_match_any()
    {
        $this->bots->registerHandlers([SillyBotHandler::class]);
        $expects = [
            'handler' => SillyBotHandler::getDTO()->toArray(),
            'match' => MessengerBots::MATCH_ANY,
            'triggers' => null,
            'admin_only' => false,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => null,
        ];

        $results = $this->resolver->resolve([
            'handler' => 'silly_bot',
            'match' => MessengerBots::MATCH_ANY,
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => ['!some', '!more'], //ignored
        ]);

        $this->assertSame($expects, $results->toArray());
    }

    /** @test */
    public function triggers_can_be_omitted_if_using_match_any()
    {
        $this->bots->registerHandlers([SillyBotHandler::class]);
        $expects = [
            'handler' => SillyBotHandler::getDTO()->toArray(),
            'match' => MessengerBots::MATCH_ANY,
            'triggers' => null, //omitted
            'admin_only' => false,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => null,
        ];

        $results = $this->resolver->resolve([
            'handler' => 'silly_bot',
            'match' => MessengerBots::MATCH_ANY,
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
        ]);

        $this->assertSame($expects, $results->toArray());
    }

    /** @test */
    public function triggers_and_match_can_be_omitted_when_match_override_is_match_any()
    {
        SillyBotHandler::$match = MessengerBots::MATCH_ANY;
        $this->bots->registerHandlers([SillyBotHandler::class]);
        $expects = [
            'handler' => SillyBotHandler::getDTO()->toArray(),
            'match' => MessengerBots::MATCH_ANY, //overwritten
            'triggers' => null, //omitted
            'admin_only' => false,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => null,
        ];

        $results = $this->resolver->resolve([
            'handler' => 'silly_bot',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
        ]);

        $this->assertSame($expects, $results->toArray());
    }

    /** @test */
    public function it_removes_trigger_overwrites_when_match_any_selected()
    {
        SillyBotHandler::$triggers = ['one', 'two'];
        $this->bots->registerHandlers([SillyBotHandler::class]);
        $expects = [
            'handler' => SillyBotHandler::getDTO()->toArray(),
            'match' => MessengerBots::MATCH_ANY,
            'triggers' => null, //omitted
            'admin_only' => false,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => null,
        ];

        $results = $this->resolver->resolve([
            'handler' => 'silly_bot',
            'match' => MessengerBots::MATCH_ANY,
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
        ]);

        $this->assertSame($expects, $results->toArray());
    }

    /** @test */
    public function overwritten_properties_can_be_omitted()
    {
        $this->bots->registerHandlers([FunBotHandler::class]);
        $expects = [
            'handler' => FunBotHandler::getDTO()->toArray(),
            'match' => MessengerBots::MATCH_EXACT_CASELESS, //overwritten
            'triggers' => '!test|!more', //overwritten
            'admin_only' => true,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => '{"test":["test"]}',
        ];
        $results = $this->resolver->resolve([
            'handler' => 'fun_bot',
            'cooldown' => 0,
            'admin_only' => true,
            'enabled' => true,
            'test' => ['test'],
        ]);

        $this->assertSame($expects, $results->toArray());
    }

    /** @test */
    public function it_formats_payload_using_handler_custom_rules_only()
    {
        $this->bots->registerHandlers([FunBotHandler::class]);
        $expects = '{"special":true,"test":{"test":"fun","more":"yes","ok":"dokie"}}';
        $results = $this->resolver->resolve([
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

        $this->assertSame($expects, $results->payload);
    }

    /**
     * @test
     *
     * @param  $match
     * @param  $cooldown
     * @param  $admin
     * @param  $enabled
     * @param  $triggers
     *
     * @dataProvider baseRulesetFailsValidation
     */
    public function it_fails_validating_base_ruleset($match, $cooldown, $admin, $enabled, $triggers)
    {
        $this->bots->registerHandlers([SillyBotHandler::class]);

        try {
            $this->resolver->resolve([
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
     *
     * @param  $triggers
     * @param  $errorKeys
     *
     * @dataProvider triggersFailValidation
     */
    public function it_fails_validating_triggers($triggers, $errorKeys)
    {
        $this->bots->registerHandlers([SillyBotHandler::class]);

        try {
            $this->resolver->resolve([
                'handler' => 'silly_bot',
                'match' => MessengerBots::MATCH_EXACT,
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
     *
     * @param  $matches
     *
     * @dataProvider passesValidatingMatches
     */
    public function it_passes_validating_matches($matches)
    {
        $this->bots->registerHandlers([SillyBotHandler::class]);
        $this->resolver->resolve([
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
     *
     * @param  $cooldown
     *
     * @dataProvider passesValidatingCooldown
     */
    public function it_passes_validating_cooldown($cooldown)
    {
        $this->bots->registerHandlers([SillyBotHandler::class]);
        $this->resolver->resolve([
            'handler' => 'silly_bot',
            'match' => MessengerBots::MATCH_EXACT,
            'cooldown' => $cooldown,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => ['test'],
        ]);

        $this->assertTrue($this->bots->isActiveHandlerSet());
    }

    /**
     * @test
     *
     * @param  $extra
     * @param  $errorKeys
     *
     * @dataProvider handlerRulesFailValidation
     */
    public function it_fails_validating_handler_rules($extra, $errorKeys)
    {
        $this->bots->registerHandlers([FunBotHandler::class]);

        try {
            $this->resolver->resolve([
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

    public static function baseRulesetFailsValidation(): array
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

    public static function triggersFailValidation(): array
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

    public static function passesValidatingMatches(): array
    {
        return [
            'any' => [MessengerBots::MATCH_ANY],
            'contains' => [MessengerBots::MATCH_CONTAINS],
            'contains caseless' => [MessengerBots::MATCH_CONTAINS_CASELESS],
            'contains any' => [MessengerBots::MATCH_CONTAINS_ANY],
            'contains any caseless' => [MessengerBots::MATCH_CONTAINS_ANY_CASELESS],
            'exact' => [MessengerBots::MATCH_EXACT],
            'exact caseless' => [MessengerBots::MATCH_EXACT_CASELESS],
            'starts with' => [MessengerBots::MATCH_STARTS_WITH],
            'starts with caseless' => [MessengerBots::MATCH_STARTS_WITH_CASELESS],
        ];
    }

    public static function passesValidatingCooldown(): array
    {
        return [
            'Can be lowest value' => [0],
            'Can be random value' => [55],
            'Can be almost highest value' => [899],
            'Can be highest value' => [900],
            'Can be 1' => [1],
        ];
    }

    public static function handlerRulesFailValidation(): array
    {
        return [
            'Attempt 1' => [null, ['test']],
            'Attempt 2' => [[null], ['test.0']],
            'Attempt 3' => [[0, 2], ['test.0', 'test.1']],
            'Attempt 4' => [['test', false, null], ['test.1', 'test.2']],
        ];
    }
}
