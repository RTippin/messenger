<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Bots\StoreBotAction;
use RTippin\Messenger\Events\NewBotActionEvent;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\FunBotHandler;
use RTippin\Messenger\Tests\Fixtures\SillyBotHandler;

class StoreBotActionTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_throws_exception_if_bots_disabled()
    {
        MessengerBots::registerHandlers([FunBotHandler::class]);
        Messenger::setBots(false);
        $thread = Thread::factory()->group()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $dto = $this->makeResolvedBotHandlerDTO(
            FunBotHandler::class,
            'exact',
            true,
            false,
            0
        );

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Bots are currently disabled.');

        app(StoreBotAction::class)->execute($thread, $bot, $dto);
    }

    /** @test */
    public function it_throws_exception_if_handler_unique_and_already_exists_on_bot()
    {
        SillyBotHandler::$authorized = true;
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = Thread::factory()->group()->create(['subject' => 'Test']);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->handler(SillyBotHandler::class)->create();
        $dto = $this->makeResolvedBotHandlerDTO(
            SillyBotHandler::class,
            'exact',
            true,
            false,
            0
        );

        $this->expectException(BotException::class);
        $this->expectExceptionMessage('You may only have one ( Silly Bot ) in Test at a time.');

        app(StoreBotAction::class)->execute($thread, $bot, $dto);
    }

    /** @test */
    public function it_throws_exception_if_handler_unique_and_already_exists_in_thread()
    {
        SillyBotHandler::$authorized = true;
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = Thread::factory()->group()->create(['subject' => 'Test']);
        $bot1 = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $bot2 = Bot::factory()->for($thread)->owner($this->tippin)->create();
        BotAction::factory()->for($bot1)->owner($this->tippin)->handler(SillyBotHandler::class)->create();
        $dto = $this->makeResolvedBotHandlerDTO(
            SillyBotHandler::class,
            'exact',
            true,
            false,
            0
        );

        $this->expectException(BotException::class);
        $this->expectExceptionMessage('You may only have one ( Silly Bot ) in Test at a time.');

        app(StoreBotAction::class)->execute($thread, $bot2, $dto);
    }

    /** @test */
    public function it_throws_exception_if_handler_fails_authorization()
    {
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = Thread::factory()->group()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create(['name' => 'Mr. Bot']);
        $dto = $this->makeResolvedBotHandlerDTO(
            SillyBotHandler::class,
            'exact',
            true,
            false,
            0
        );

        $this->expectException(BotException::class);
        $this->expectExceptionMessage('Not authorized to add ( Silly Bot ) to Mr. Bot.');

        app(StoreBotAction::class)->execute($thread, $bot, $dto);
    }

    /** @test */
    public function it_can_skip_authorization_checks()
    {
        Messenger::setBots(false);
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = Thread::factory()->group()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create(['name' => 'Mr. Bot']);
        BotAction::factory()->for($bot)->owner($this->tippin)->handler(SillyBotHandler::class)->create();
        $dto = $this->makeResolvedBotHandlerDTO(
            SillyBotHandler::class,
            'exact',
            true,
            false,
            0
        );

        app(StoreBotAction::class)->execute($thread, $bot, $dto, true);

        $this->assertDatabaseCount('bot_actions', 2);
    }

    /** @test */
    public function it_stores_bot_action_that_passes_authorization()
    {
        SillyBotHandler::$authorized = true;
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = Thread::factory()->group()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $dto = $this->makeResolvedBotHandlerDTO(
            SillyBotHandler::class,
            'exact',
            true,
            false,
            0,
            'test'
        );

        app(StoreBotAction::class)->execute($thread, $bot, $dto);

        $this->assertDatabaseCount('bot_actions', 1);
    }

    /** @test */
    public function it_stores_bot_action_and_clears_actions_cache()
    {
        MessengerBots::registerHandlers([FunBotHandler::class]);
        $thread = Thread::factory()->group()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $dto = $this->makeResolvedBotHandlerDTO(
            FunBotHandler::class,
            'exact',
            true,
            false,
            0,
            'test'
        );
        $cache = Cache::spy();

        app(StoreBotAction::class)->execute($thread, $bot, $dto);

        $cache->shouldHaveReceived('forget');
        $this->assertDatabaseHas('bot_actions', [
            'bot_id' => $bot->id,
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'handler' => FunBotHandler::class,
            'match' => 'exact',
            'triggers' => 'test',
            'admin_only' => false,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => null,
        ]);
    }

    /** @test */
    public function it_stores_reused_handler_when_not_unique()
    {
        MessengerBots::registerHandlers([FunBotHandler::class]);
        $thread = Thread::factory()->group()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->handler(FunBotHandler::class)->create();
        $dto = $this->makeResolvedBotHandlerDTO(
            FunBotHandler::class,
            'exact',
            true,
            false,
            0,
            'test'
        );

        app(StoreBotAction::class)->execute($thread, $bot, $dto);

        $this->assertDatabaseCount('bot_actions', 2);
    }

    /** @test */
    public function it_fires_events()
    {
        MessengerBots::registerHandlers([FunBotHandler::class]);
        BaseMessengerAction::enableEvents();
        $thread = Thread::factory()->group()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $dto = $this->makeResolvedBotHandlerDTO(
            FunBotHandler::class,
            'exact',
            true,
            false,
            0,
            'test'
        );
        Event::fake([
            NewBotActionEvent::class,
        ]);

        app(StoreBotAction::class)->execute($thread, $bot, $dto);

        Event::assertDispatched(function (NewBotActionEvent $event) use ($bot) {
            return $bot->id === $event->botAction->bot_id;
        });
    }
}
