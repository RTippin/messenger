<?php

namespace RTippin\Messenger\Tests\Actions;

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
        Messenger::setBots(false);
        $thread = Thread::factory()->group()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Bots are currently disabled.');

        app(StoreBotAction::class)->execute($thread, $bot, []);
    }

    /** @test */
    public function it_throws_exception_if_handler_unique_and_already_exists_in_thread()
    {
        $thread = Thread::factory()->group()->create(['subject' => 'Test']);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->handler('ReplyBot')->create();

        $this->expectException(BotException::class);
        $this->expectExceptionMessage('You may only have one (Bot Name) in Test at a time.');

        app(StoreBotAction::class)->execute($thread, $bot, [
            'handler' => 'ReplyBot',
            'unique' => true,
            'authorize' => false,
            'name' => 'Bot Name',
            'match' => 'exact',
            'triggers' => 'test',
            'admin_only' => false,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => null,
        ]);
    }

    /** @test */
    public function it_throws_exception_if_handler_fails_authorization()
    {
        MessengerBots::setHandlers([SillyBotHandler::class]);
        $thread = Thread::factory()->group()->create(['subject' => 'Test']);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();

        $this->expectException(BotException::class);
        $this->expectExceptionMessage('Not authorized to add (Bot Name) to Test.');

        app(StoreBotAction::class)->execute($thread, $bot, [
            'handler' => SillyBotHandler::class,
            'unique' => true,
            'authorize' => true,
            'name' => 'Bot Name',
            'match' => 'exact',
            'triggers' => 'test',
            'admin_only' => false,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => null,
        ]);
    }

    /** @test */
    public function it_stores_bot_action()
    {
        $thread = Thread::factory()->group()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();

        app(StoreBotAction::class)->execute($thread, $bot, [
            'handler' => 'ReplyBot',
            'unique' => false,
            'authorize' => false,
            'name' => 'Bot Name',
            'match' => 'exact',
            'triggers' => 'test',
            'admin_only' => false,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => null,
        ]);

        $this->assertDatabaseHas('bot_actions', [
            'bot_id' => $bot->id,
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'handler' => 'ReplyBot',
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
        $thread = Thread::factory()->group()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        BotAction::factory()->for(
            Bot::factory()->for($thread)->owner($this->tippin)->create()
        )->owner($this->tippin)->handler('ReplyBot')->create();

        app(StoreBotAction::class)->execute($thread, $bot, [
            'handler' => 'ReplyBot',
            'unique' => false,
            'authorize' => false,
            'name' => 'Bot Name',
            'match' => 'exact',
            'triggers' => 'test',
            'admin_only' => false,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => null,
        ]);

        $this->assertDatabaseCount('bot_actions', 2);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        $thread = Thread::factory()->group()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        Event::fake([
            NewBotActionEvent::class,
        ]);

        app(StoreBotAction::class)->execute($thread, $bot, [
            'handler' => 'ReplyBot',
            'unique' => false,
            'authorize' => false,
            'name' => 'Bot Name',
            'match' => 'exact',
            'triggers' => 'test',
            'admin_only' => false,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => null,
        ]);

        Event::assertDispatched(function (NewBotActionEvent $event) use ($bot) {
            return $bot->id === $event->botAction->bot_id;
        });
    }
}
