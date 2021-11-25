<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\Fixtures\FunBotHandler;
use RTippin\Messenger\Tests\Fixtures\SillyBotHandler;
use RTippin\Messenger\Tests\HttpTestCase;

class AvailableBotHandlersTest extends HttpTestCase
{
    /** @test */
    public function admin_can_view_available_handlers()
    {
        $this->logCurrentRequest();
        SillyBotHandler::$authorized = true;
        MessengerBots::registerHandlers([
            FunBotHandler::class,
            SillyBotHandler::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.handlers', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                [
                    'alias' => 'fun_bot',
                ],
                [
                    'alias' => 'silly_bot',
                ],
            ]);
    }

    /** @test */
    public function unique_handlers_that_are_already_attached_to_bot_are_omitted()
    {
        SillyBotHandler::$authorized = true;
        MessengerBots::registerHandlers([
            FunBotHandler::class,
            SillyBotHandler::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->handler(FunBotHandler::class)->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->handler(SillyBotHandler::class)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.handlers', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'alias' => 'fun_bot',
                ],
            ]);
    }

    /** @test */
    public function unique_handlers_that_are_already_attached_to_another_bot_are_omitted()
    {
        SillyBotHandler::$authorized = true;
        MessengerBots::registerHandlers([
            FunBotHandler::class,
            SillyBotHandler::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);
        $bot1 = Bot::factory()->for($thread)->owner($this->tippin)->create();
        BotAction::factory()->for($bot1)->owner($this->tippin)->handler(FunBotHandler::class)->create();
        BotAction::factory()->for($bot1)->owner($this->tippin)->handler(SillyBotHandler::class)->create();
        $bot2 = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.handlers', [
            'thread' => $thread->id,
            'bot' => $bot2->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'alias' => 'fun_bot',
                ],
            ]);
    }

    /** @test */
    public function handlers_failing_authorization_are_omitted()
    {
        MessengerBots::registerHandlers([
            FunBotHandler::class,
            SillyBotHandler::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.handlers', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1)
            ->assertJson([
                [
                    'alias' => 'fun_bot',
                ],
            ]);
    }

    /** @test */
    public function participant_with_permission_can_view_available_handlers()
    {
        MessengerBots::registerHandlers([FunBotHandler::class]);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create(['manage_bots' => true]);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.bots.handlers', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function participant_without_permission_forbidden_to_view_available_handlers()
    {
        $this->logCurrentRequest();
        MessengerBots::registerHandlers([FunBotHandler::class]);
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.bots.handlers', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_view_available_handlers_when_disabled_in_config()
    {
        Messenger::setBots(false);
        MessengerBots::registerHandlers([FunBotHandler::class]);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.handlers', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_view_available_handlers_when_disabled_in_thread_settings()
    {
        MessengerBots::registerHandlers([FunBotHandler::class]);
        $thread = Thread::factory()->group()->create(['chat_bots' => false]);
        Participant::factory()->for($thread)->admin()->owner($this->tippin)->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.handlers', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertForbidden();
    }
}
