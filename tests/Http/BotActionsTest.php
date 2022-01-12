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

class BotActionsTest extends HttpTestCase
{
    /** @test */
    public function admin_can_view_actions()
    {
        $this->logCurrentRequest();
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->count(2)->handler(SillyBotHandler::class)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.actions.index', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(2);
    }

    /** @test */
    public function forbidden_to_view_actions_when_disabled_in_config()
    {
        $this->logCurrentRequest();
        Messenger::setBots(false);
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->handler(SillyBotHandler::class)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.actions.index', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_view_actions_when_disabled_in_thread_settings()
    {
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = Thread::factory()->group()->create(['chat_bots' => false]);
        Participant::factory()->for($thread)->admin()->owner($this->tippin)->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->handler(SillyBotHandler::class)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.actions.index', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function participant_can_view_actions()
    {
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->handler(SillyBotHandler::class)->create();
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.bots.actions.index', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1);
    }

    /** @test */
    public function participant_without_permission_forbidden_to_view_hidden_actions()
    {
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->hideActions()->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->handler(SillyBotHandler::class)->create();
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.bots.actions.index', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function participant_with_permission_can_view_hidden_actions()
    {
        MessengerBots::registerHandlers([FunBotHandler::class]);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create(['manage_bots' => true]);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->hideActions()->create();
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.bots.actions.index', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function admin_can_view_actions_when_hidden()
    {
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->hideActions()->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->handler(SillyBotHandler::class)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.actions.index', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function admin_can_view_action()
    {
        $this->logCurrentRequest();
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->handler(SillyBotHandler::class)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.actions.show', [
            'thread' => $thread->id,
            'bot' => $bot->id,
            'action' => $action->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $action->id,
            ]);
    }

    /** @test */
    public function forbidden_to_view_action_when_disabled_in_config()
    {
        $this->logCurrentRequest();
        Messenger::setBots(false);
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->handler(SillyBotHandler::class)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.actions.show', [
            'thread' => $thread->id,
            'bot' => $bot->id,
            'action' => $action->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_view_action_when_disabled_in_thread_settings()
    {
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = Thread::factory()->group()->create(['chat_bots' => false]);
        Participant::factory()->for($thread)->admin()->owner($this->tippin)->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->handler(SillyBotHandler::class)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.actions.show', [
            'thread' => $thread->id,
            'bot' => $bot->id,
            'action' => $action->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function participant_can_view_action()
    {
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->handler(SillyBotHandler::class)->create();
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.bots.actions.show', [
            'thread' => $thread->id,
            'bot' => $bot->id,
            'action' => $action->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $action->id,
            ]);
    }

    /** @test */
    public function admin_can_store_action()
    {
        $this->logCurrentRequest();
        MessengerBots::registerHandlers([FunBotHandler::class]);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.actions.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'handler' => 'fun_bot',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => ['test'],
            'test' => ['test'],
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function user_can_store_action_when_handler_auth_passes()
    {
        SillyBotHandler::$authorized = true;
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.actions.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'handler' => 'silly_bot',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => ['test'],
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function forbidden_to_store_action_when_handler_auth_fails()
    {
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.actions.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'handler' => 'silly_bot',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => ['test'],
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_store_action_when_handler_unique_and_exists_in_thread()
    {
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->handler(SillyBotHandler::class)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.actions.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'handler' => 'silly_bot',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => ['test'],
        ])
            ->assertForbidden();
    }

    /** @test */
    public function user_can_store_action_without_triggers_using_match_any()
    {
        SillyBotHandler::$authorized = true;
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.actions.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'handler' => 'silly_bot',
            'match' => 'any',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
        ])
            ->assertSuccessful()
            ->assertJson([
                'triggers' => [],
            ]);
    }

    /** @test */
    public function participant_with_permission_can_store_action()
    {
        MessengerBots::registerHandlers([FunBotHandler::class]);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create(['manage_bots' => true]);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.bots.actions.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'handler' => 'fun_bot',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => ['test'],
            'test' => ['test'],
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function participant_without_permission_forbidden_to_store_action()
    {
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.bots.actions.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'handler' => 'silly_bot',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => ['test'],
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_store_action_when_bots_disabled_from_config()
    {
        $this->logCurrentRequest();
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        Messenger::setBots(false);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.actions.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'handler' => 'silly_bot',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => ['test'],
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_store_action_when_disabled_in_thread()
    {
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = Thread::factory()->group()->create(['chat_bots' => false]);
        Participant::factory()->for($thread)->admin()->owner($this->tippin)->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.bots.actions.store', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]), [
            'handler' => 'silly_bot',
            'match' => 'exact',
            'cooldown' => 0,
            'admin_only' => false,
            'enabled' => true,
            'triggers' => ['test'],
        ])
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_remove_action()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.bots.actions.destroy', [
            'thread' => $thread->id,
            'bot' => $bot->id,
            'action' => $action->id,
        ]))
            ->assertStatus(204);
    }

    /** @test */
    public function participant_with_permission_can_remove_action()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create(['manage_bots' => true]);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->deleteJson(route('api.messenger.threads.bots.actions.destroy', [
            'thread' => $thread->id,
            'bot' => $bot->id,
            'action' => $action->id,
        ]))
            ->assertStatus(204);
    }

    /** @test */
    public function participant_without_permission_forbidden_to_remove_action()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->deleteJson(route('api.messenger.threads.bots.actions.destroy', [
            'thread' => $thread->id,
            'bot' => $bot->id,
            'action' => $action->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_remove_action_when_disabled_in_config()
    {
        Messenger::setBots(false);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.bots.actions.destroy', [
            'thread' => $thread->id,
            'bot' => $bot->id,
            'action' => $action->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_remove_action_when_disabled_in_thread_settings()
    {
        $thread = Thread::factory()->group()->create(['chat_bots' => false]);
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.bots.actions.destroy', [
            'thread' => $thread->id,
            'bot' => $bot->id,
            'action' => $action->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_update_action()
    {
        $this->logCurrentRequest();
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->handler(SillyBotHandler::class)->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.bots.actions.update', [
            'thread' => $thread->id,
            'bot' => $bot->id,
            'action' => $action->id,
        ]), [
            'match' => 'contains',
            'cooldown' => 99,
            'admin_only' => true,
            'enabled' => false,
            'triggers' => ['test', 'more'],
        ])
            ->assertSuccessful()
            ->assertJson([
                'match' => 'contains',
                'cooldown' => 99,
                'admin_only' => true,
                'enabled' => false,
                'triggers' => ['test', 'more'],
            ]);
    }

    /** @test */
    public function participant_with_permission_can_update_action()
    {
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create(['manage_bots' => true]);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->handler(SillyBotHandler::class)->create();
        $this->actingAs($this->doe);

        $this->putJson(route('api.messenger.threads.bots.actions.update', [
            'thread' => $thread->id,
            'bot' => $bot->id,
            'action' => $action->id,
        ]), [
            'match' => 'contains',
            'cooldown' => 99,
            'admin_only' => true,
            'enabled' => false,
            'triggers' => ['test', 'more'],
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function forbidden_to_update_action_when_disabled_in_config()
    {
        $this->logCurrentRequest();
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        Messenger::setBots(false);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->handler(SillyBotHandler::class)->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.bots.actions.update', [
            'thread' => $thread->id,
            'bot' => $bot->id,
            'action' => $action->id,
        ]), [
            'match' => 'contains',
            'cooldown' => 99,
            'admin_only' => true,
            'enabled' => false,
            'triggers' => ['test', 'more'],
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_update_action_when_disabled_in_thread()
    {
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = Thread::factory()->group()->create(['chat_bots' => false]);
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->handler(SillyBotHandler::class)->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.bots.actions.update', [
            'thread' => $thread->id,
            'bot' => $bot->id,
            'action' => $action->id,
        ]), [
            'match' => 'contains',
            'cooldown' => 99,
            'admin_only' => true,
            'enabled' => false,
            'triggers' => ['test', 'more'],
        ])
            ->assertForbidden();
    }

    /** @test */
    public function participant_without_permission_forbidden_to_update_action()
    {
        MessengerBots::registerHandlers([SillyBotHandler::class]);
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->handler(SillyBotHandler::class)->create();
        $this->actingAs($this->doe);

        $this->putJson(route('api.messenger.threads.bots.actions.update', [
            'thread' => $thread->id,
            'bot' => $bot->id,
            'action' => $action->id,
        ]), [
            'match' => 'contains',
            'cooldown' => 99,
            'admin_only' => true,
            'enabled' => false,
            'triggers' => ['test', 'more'],
        ])
            ->assertForbidden();
    }
}
