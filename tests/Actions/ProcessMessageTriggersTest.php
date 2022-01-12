<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Bots\ProcessMessageTriggers;
use RTippin\Messenger\Events\BotActionFailedEvent;
use RTippin\Messenger\Events\BotActionHandledEvent;
use RTippin\Messenger\Exceptions\BotException;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\BrokenBotHandler;
use RTippin\Messenger\Tests\Fixtures\FunBotHandler;
use RTippin\Messenger\Tests\Fixtures\SillyBotHandler;

class ProcessMessageTriggersTest extends FeatureTestCase
{
    /** @test */
    public function it_throws_exception_if_disabled()
    {
        Messenger::setBots(false);
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Bots are currently disabled.');

        app(ProcessMessageTriggers::class)->execute($thread, $message);
    }

    /** @test */
    public function it_executes_handle()
    {
        MessengerBots::registerHandlers([FunBotHandler::class]);
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => '!test']);
        BotAction::factory()
            ->for(Bot::factory()->for($thread)->owner($this->tippin)->create())
            ->owner($this->tippin)
            ->handler(FunBotHandler::class)
            ->triggers('!test')
            ->create();

        app(ProcessMessageTriggers::class)->execute($thread, $message);

        $this->assertDatabaseHas('messages', [
            'body' => 'Testing Fun.',
        ]);
    }

    /** @test */
    public function it_executes_handle_ignoring_message_text_if_match_any()
    {
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => 'this will not match a trigger but matches any']);
        BotAction::factory()
            ->for(Bot::factory()->for($thread)->owner($this->tippin)->create())
            ->owner($this->tippin)
            ->handler(SillyBotHandler::class)
            ->triggers('test')
            ->match('any')
            ->create();

        app(ProcessMessageTriggers::class)->execute($thread, $message);

        $this->assertDatabaseCount('messages', 2);
    }

    /** @test */
    public function it_executes_handle_once_if_multiple_triggers_match_for_a_single_action()
    {
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => 'test testing']);
        BotAction::factory()
            ->for(Bot::factory()->for($thread)->owner($this->tippin)->create())
            ->owner($this->tippin)
            ->handler(SillyBotHandler::class)
            ->triggers('test|testing')
            ->match('contains:caseless')
            ->create();

        app(ProcessMessageTriggers::class)->execute($thread, $message);

        $this->assertDatabaseCount('messages', 2);
    }

    /** @test */
    public function it_flushes_active_handler_before_and_after_executing()
    {
        MessengerBots::registerHandlers([
            FunBotHandler::class,
            SillyBotHandler::class,
        ]);
        MessengerBots::initializeHandler(SillyBotHandler::class);
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => '!test']);
        BotAction::factory()
            ->for(Bot::factory()->for($thread)->owner($this->tippin)->create())
            ->owner($this->tippin)
            ->handler(FunBotHandler::class)
            ->triggers('!test')
            ->create();

        $this->assertTrue(MessengerBots::isActiveHandlerSet());

        app(ProcessMessageTriggers::class)->execute($thread, $message);

        $this->assertDatabaseHas('messages', [
            'body' => 'Testing Fun.',
        ]);
        $this->assertFalse(MessengerBots::isActiveHandlerSet());
    }

    /** @test */
    public function it_does_nothing_if_no_match_found()
    {
        MessengerBots::registerHandlers([FunBotHandler::class]);
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => '!unknown']);
        BotAction::factory()
            ->for(Bot::factory()->for($thread)->owner($this->tippin)->create())
            ->owner($this->tippin)
            ->handler(FunBotHandler::class)
            ->triggers('!test|!more')
            ->create();

        app(ProcessMessageTriggers::class)->execute($thread, $message);

        $this->assertDatabaseMissing('messages', [
            'body' => 'Testing Fun.',
        ]);
    }

    /** @test */
    public function it_does_nothing_if_no_match_found_while_using_handler_overrides()
    {
        MessengerBots::registerHandlers([FunBotHandler::class]);
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => '!unknown']);
        BotAction::factory()
            ->for(Bot::factory()->for($thread)->owner($this->tippin)->create())
            ->owner($this->tippin)
            ->handler(FunBotHandler::class)
            ->triggers('!unknown')
            ->create();

        app(ProcessMessageTriggers::class)->execute($thread, $message);

        $this->assertDatabaseMissing('messages', [
            'body' => 'Testing Fun.',
        ]);
    }

    /** @test */
    public function it_executes_handle_if_admin()
    {
        MessengerBots::registerHandlers([FunBotHandler::class]);
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => '!test']);
        BotAction::factory()
            ->for(Bot::factory()->for($thread)->owner($this->tippin)->create())
            ->owner($this->tippin)
            ->handler(FunBotHandler::class)
            ->triggers('!test')
            ->admin()
            ->create();

        app(ProcessMessageTriggers::class)->execute($thread, $message, true);

        $this->assertDatabaseHas('messages', [
            'body' => 'Testing Fun.',
        ]);
    }

    /** @test */
    public function it_doesnt_execute_handle_if_not_admin()
    {
        MessengerBots::registerHandlers([FunBotHandler::class]);
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => '!test']);
        BotAction::factory()
            ->for(Bot::factory()->for($thread)->owner($this->tippin)->create())
            ->owner($this->tippin)
            ->handler(FunBotHandler::class)
            ->triggers('!test')
            ->admin()
            ->create();

        app(ProcessMessageTriggers::class)->execute($thread, $message);

        $this->assertDatabaseMissing('messages', [
            'body' => 'Testing Fun.',
        ]);
    }

    /** @test */
    public function it_forwards_sender_ip_to_handler()
    {
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => '!test']);
        BotAction::factory()
            ->for(Bot::factory()->for($thread)->owner($this->tippin)->create())
            ->owner($this->tippin)
            ->handler(SillyBotHandler::class)
            ->triggers('!test')
            ->admin()
            ->create();

        app(ProcessMessageTriggers::class)->execute($thread, $message, true, '127.0.0.1');

        $this->assertDatabaseHas('messages', [
            'body' => 'Testing Silly. 127.0.0.1',
        ]);
    }

    /** @test */
    public function it_sets_action_and_bot_cooldowns()
    {
        MessengerBots::registerHandlers([FunBotHandler::class]);
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => '!test']);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create(['cooldown' => 30]);
        $action = BotAction::factory()
            ->for($bot)
            ->owner($this->tippin)
            ->handler(FunBotHandler::class)
            ->triggers('!test')
            ->create(['cooldown' => 30]);

        app(ProcessMessageTriggers::class)->execute($thread, $message, true);

        $this->assertTrue(Cache::has("bot:$bot->id:cooldown"));
        $this->assertTrue(Cache::has("bot:$bot->id:$action->id:cooldown"));
    }

    /** @test */
    public function it_can_release_action_cooldown()
    {
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => '!test']);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create(['cooldown' => 30]);
        $action = BotAction::factory()
            ->for($bot)
            ->owner($this->tippin)
            ->handler(SillyBotHandler::class)
            ->triggers('!test')
            ->create(['cooldown' => 30]);

        app(ProcessMessageTriggers::class)->execute($thread, $message, true);

        $this->assertTrue(Cache::has("bot:$bot->id:cooldown"));
        $this->assertFalse(Cache::has("bot:$bot->id:$action->id:cooldown"));
    }

    /** @test */
    public function it_executes_multiple_handles()
    {
        MessengerBots::registerHandlers([
            FunBotHandler::class,
            SillyBotHandler::class,
        ]);
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => '!test']);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        BotAction::factory()
            ->for($bot)
            ->owner($this->tippin)
            ->sequence(
                ['handler' => FunBotHandler::class],
                ['handler' => SillyBotHandler::class],
            )
            ->triggers('!test')
            ->count(2)
            ->create();

        app(ProcessMessageTriggers::class)->execute($thread, $message, true);

        $this->assertDatabaseCount('messages', 3);
    }

    /** @test */
    public function it_does_nothing_if_bot_on_cooldown()
    {
        MessengerBots::registerHandlers([FunBotHandler::class]);
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => '!test']);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        BotAction::factory()
            ->for($bot)
            ->owner($this->tippin)
            ->handler(FunBotHandler::class)
            ->triggers('!test')
            ->create();
        Cache::put("bot:$bot->id:cooldown", true, now()->addSeconds(30));

        app(ProcessMessageTriggers::class)->execute($thread, $message, true);

        $this->assertDatabaseCount('messages', 1);
    }

    /** @test */
    public function it_does_nothing_if_action_on_cooldown()
    {
        MessengerBots::registerHandlers([FunBotHandler::class]);
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => '!test']);
        $action = BotAction::factory()
            ->for(Bot::factory()->for($thread)->owner($this->tippin)->create())
            ->owner($this->tippin)
            ->handler(FunBotHandler::class)
            ->triggers('!test')
            ->create();
        Cache::put("bot:$action->bot_id:$action->id:cooldown", true, now()->addSeconds(30));

        app(ProcessMessageTriggers::class)->execute($thread, $message, true);

        $this->assertDatabaseCount('messages', 1);
    }

    /** @test */
    public function it_does_nothing_if_no_valid_handlers_found()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => '!test']);
        BotAction::factory()
            ->for(Bot::factory()->for($thread)->owner($this->tippin)->create())
            ->owner($this->tippin)
            ->triggers('!test')
            ->create();

        app(ProcessMessageTriggers::class)->execute($thread, $message, true);

        $this->assertDatabaseCount('messages', 1);
    }

    /** @test */
    public function it_fires_handled_event()
    {
        BaseMessengerAction::enableEvents();
        MessengerBots::registerHandlers([FunBotHandler::class]);
        Event::fake([
            BotActionHandledEvent::class,
            BotActionFailedEvent::class,
        ]);
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => '!test']);
        $action = BotAction::factory()
            ->for(Bot::factory()->for($thread)->owner($this->tippin)->create())
            ->owner($this->tippin)
            ->handler(FunBotHandler::class)
            ->triggers('!test')
            ->create();

        app(ProcessMessageTriggers::class)->execute($thread, $message, true);

        Event::assertNotDispatched(BotActionFailedEvent::class);
        Event::assertDispatched(function (BotActionHandledEvent $event) use ($action, $message) {
            $this->assertSame($action->id, $event->action->id);
            $this->assertSame($message->id, $event->message->id);
            $this->assertSame('!test', $event->trigger);

            return true;
        });
    }

    /** @test */
    public function it_fires_multiple_event()
    {
        BaseMessengerAction::enableEvents();
        MessengerBots::registerHandlers([
            FunBotHandler::class,
            SillyBotHandler::class,
            BrokenBotHandler::class,
        ]);
        Event::fake([
            BotActionHandledEvent::class,
            BotActionFailedEvent::class,
        ]);
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => '!test']);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        BotAction::factory()
            ->for($bot)
            ->owner($this->tippin)
            ->sequence(
                ['handler' => FunBotHandler::class],
                ['handler' => SillyBotHandler::class],
                ['handler' => BrokenBotHandler::class],
            )
            ->triggers('!test')
            ->count(3)
            ->create();

        app(ProcessMessageTriggers::class)->execute($thread, $message, true);

        Event::assertDispatchedTimes(BotActionHandledEvent::class, 2);
        Event::assertDispatchedTimes(BotActionFailedEvent::class, 1);
    }

    /** @test */
    public function it_fires_failed_event_if_handler_throws_exception()
    {
        BaseMessengerAction::enableEvents();
        MessengerBots::registerHandlers([BrokenBotHandler::class]);
        Event::fake([
            BotActionHandledEvent::class,
            BotActionFailedEvent::class,
        ]);
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create(['body' => '!test']);
        $action = BotAction::factory()
            ->for(Bot::factory()->for($thread)->owner($this->tippin)->create())
            ->owner($this->tippin)
            ->handler(BrokenBotHandler::class)
            ->triggers('!test')
            ->create();

        app(ProcessMessageTriggers::class)->execute($thread, $message, true);

        Event::assertNotDispatched(BotActionHandledEvent::class);
        Event::assertDispatched(function (BotActionFailedEvent $event) use ($action) {
            $this->assertSame($action->id, $event->action->id);
            $this->assertInstanceOf(BotException::class, $event->exception);

            return true;
        });
    }
}
