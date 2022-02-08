<?php

namespace RTippin\Messenger\Tests\Support;

use RTippin\Messenger\DataTransferObjects\BotActionHandlerDTO;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Facades\MessengerBots as MessengerBotsFacade;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\BotActionHandler;
use RTippin\Messenger\Support\MessengerComposer;
use RTippin\Messenger\Tests\Fixtures\BrokenBotHandler;
use RTippin\Messenger\Tests\Fixtures\FunBotHandler;
use RTippin\Messenger\Tests\Fixtures\SillyBotHandler;
use RTippin\Messenger\Tests\MessengerTestCase;

class BotActionHandlerTest extends MessengerTestCase
{
    /** @test */
    public function it_shows_handlers_not_testing_by_default()
    {
        $this->assertFalse(FunBotHandler::isTesting());
        $this->assertFalse(SillyBotHandler::isTesting());
        $this->assertFalse(BrokenBotHandler::isTesting());
    }

    /** @test */
    public function it_can_set_handlers_to_testing()
    {
        BotActionHandler::isTesting(true);

        $this->assertTrue(FunBotHandler::isTesting());
        $this->assertTrue(SillyBotHandler::isTesting());
        $this->assertTrue(BrokenBotHandler::isTesting());
    }

    /** @test */
    public function it_has_settings()
    {
        $expects = [
            'alias' => 'fun_bot',
            'description' => 'This is a fun bot.',
            'name' => 'Fun Bot',
            'triggers' => ['!test', '!more'],
            'match' => MessengerBots::MATCH_EXACT_CASELESS,
        ];

        $this->assertSame($expects, FunBotHandler::getSettings());
    }

    /** @test */
    public function it_doesnt_have_dto_if_not_registered()
    {
        $this->assertNull(FunBotHandler::getDTO());
    }

    /** @test */
    public function it_can_get_dto()
    {
        MessengerBotsFacade::registerHandlers([FunBotHandler::class]);
        $handler = FunBotHandler::getDTO();

        $this->assertInstanceOf(BotActionHandlerDTO::class, $handler);
        $this->assertSame('fun_bot', $handler->alias);
    }

    /** @test */
    public function it_registers_handler_when_testing_resolve()
    {
        $this->assertFalse(MessengerBotsFacade::isValidHandler(BrokenBotHandler::class));

        BrokenBotHandler::testResolve();

        $this->assertTrue(MessengerBotsFacade::isValidHandler(BrokenBotHandler::class));
    }

    /** @test */
    public function it_can_test_resolving_handler()
    {
        $resolved = FunBotHandler::testResolve([
            'match' => MessengerBots::MATCH_CONTAINS,
            'cooldown' => 0,
            'admin_only' => true,
            'enabled' => true,
            'test' => ['test'],
            'special' => true,
        ]);
        $expects = [
            'handler' => FunBotHandler::getDTO()->toArray(),
            'match' => MessengerBots::MATCH_EXACT_CASELESS,
            'triggers' => '!test|!more',
            'admin_only' => true,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => '{"special":true,"test":["test"]}',
        ];

        $this->assertSame($expects, $resolved->toArray());
    }

    /** @test */
    public function it_returns_validation_errors_if_testing_resolve_fails()
    {
        $resolved = FunBotHandler::testResolve();
        $expects = [
            'cooldown' => ['The cooldown field is required.'],
            'admin_only' => ['The admin only field is required.'],
            'enabled' => ['The enabled field is required.'],
            'test' => ['Tests must be string.'],
        ];

        $this->assertSame($expects, $resolved);
    }

    /** @test */
    public function it_can_call_to_release_cooldown()
    {
        $handler = new FunBotHandler;

        $this->assertFalse($handler->shouldReleaseCooldown());

        $handler->releaseCooldown();

        $this->assertTrue($handler->shouldReleaseCooldown());
    }

    /** @test */
    public function it_has_rules()
    {
        $overrides = [
            'test' => ['required', 'array', 'min:1'],
            'test.*' => ['required', 'string'],
            'special' => ['nullable', 'boolean'],
        ];

        $this->assertSame($overrides, (new FunBotHandler)->rules());
        $this->assertSame([], (new SillyBotHandler)->rules());
    }

    /** @test */
    public function it_has_error_messages()
    {
        $overrides = [
            'test' => 'Test Needed.',
            'test.*' => 'Tests must be string.',
        ];

        $this->assertSame($overrides, (new FunBotHandler)->errorMessages());
        $this->assertSame([], (new SillyBotHandler)->errorMessages());
    }

    /** @test */
    public function it_can_be_handled()
    {
        $this->expectException(BotException::class);

        (new BrokenBotHandler)->handle();
    }

    /** @test */
    public function it_can_serialize_payload()
    {
        $handler = new FunBotHandler;

        $this->assertNull($handler->serializePayload(null));
        $this->assertSame('{"test":true}', $handler->serializePayload(['test' => true]));
    }

    /** @test */
    public function it_can_access_messenger_composer_and_sets_bot_as_provider()
    {
        $thread = Thread::factory()->group()->make();
        $message = Message::factory()->make();
        $bot = Bot::factory()->for($thread)->make();
        $action = BotAction::factory()->for($bot)->make();
        $action->setRelation('bot', $bot);
        $handler = (new FunBotHandler)->setDataForHandler($thread, $action, $message);

        $composer = $handler->composer();

        $this->assertInstanceOf(MessengerComposer::class, $composer);
        $this->assertSame(Messenger::getProvider(), $bot);
    }

    /** @test */
    public function it_can_get_actions_payload()
    {
        $thread = Thread::factory()->group()->make();
        $message = Message::factory()->make();
        $action = BotAction::factory()
            ->payload('{"test":{"test":"fun","more":"yes","ok":"dokie"},"special":true}')
            ->make();
        $emptyAction = BotAction::factory()->make();

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
        $thread = Thread::factory()->group()->make();
        $message = Message::factory()->body('!command Do Something Fun')->make();
        $action = BotAction::factory()->make();

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
        $thread = Thread::factory()->group()->make();
        $message = Message::factory()->body('!command Do Something Fun')->make();
        $action = BotAction::factory()->make();

        $handler = (new FunBotHandler)->setDataForHandler($thread, $action, $message);

        $this->assertSame('!command Do Something Fun', $handler->getParsedMessage());
        $this->assertSame('!command do something fun', $handler->getParsedMessage(true));
        $this->assertSame(['!command', 'Do', 'Something', 'Fun'], $handler->getParsedWords());
        $this->assertSame(['!command', 'do', 'something', 'fun'], $handler->getParsedWords(true));
    }
}
