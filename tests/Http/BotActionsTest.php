<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Facades\MessengerBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\TestBotHandler;
use RTippin\Messenger\Tests\Fixtures\TestBotTwoHandler;

class BotActionsTest extends FeatureTestCase
{
    /** @test */
    public function admin_can_view_actions()
    {
        MessengerBots::setHandlers([TestBotTwoHandler::class]);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->count(2)->handler(TestBotTwoHandler::class)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.bots.actions.index', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(2);
    }

    /** @test */
    public function participant_can_view_actions()
    {
        MessengerBots::setHandlers([TestBotTwoHandler::class]);
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        BotAction::factory()->for($bot)->owner($this->tippin)->handler(TestBotTwoHandler::class)->create();
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.bots.actions.index', [
            'thread' => $thread->id,
            'bot' => $bot->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(1);
    }

    /** @test */
    public function admin_can_view_action()
    {
        MessengerBots::setHandlers([TestBotTwoHandler::class]);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->handler(TestBotTwoHandler::class)->create();
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
    public function participant_can_view_action()
    {
        MessengerBots::setHandlers([TestBotTwoHandler::class]);
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->handler(TestBotTwoHandler::class)->create();
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
        MessengerBots::setHandlers([TestBotHandler::class]);
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
    public function participant_with_permission_can_store_action()
    {
        MessengerBots::setHandlers([TestBotHandler::class]);
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
        MessengerBots::setHandlers([TestBotTwoHandler::class]);
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
        MessengerBots::setHandlers([TestBotTwoHandler::class]);
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
        MessengerBots::setHandlers([TestBotTwoHandler::class]);
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
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.bots.actions.destroy', [
            'thread' => $thread->id,
            'bot' => $bot->id,
            'action' => $action->id,
        ]))
            ->assertSuccessful();
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
            ->assertSuccessful();
    }

    /** @test */
    public function participant_without_permission_forbidden_to_remove_action()
    {
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
        MessengerBots::setHandlers([TestBotTwoHandler::class]);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->handler(TestBotTwoHandler::class)->create();
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
        MessengerBots::setHandlers([TestBotTwoHandler::class]);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create(['manage_bots' => true]);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->handler(TestBotTwoHandler::class)->create();
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
        MessengerBots::setHandlers([TestBotTwoHandler::class]);
        Messenger::setBots(false);
        $thread = $this->createGroupThread($this->tippin);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->handler(TestBotTwoHandler::class)->create();
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
        MessengerBots::setHandlers([TestBotTwoHandler::class]);
        $thread = Thread::factory()->group()->create(['chat_bots' => false]);
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->handler(TestBotTwoHandler::class)->create();
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
        MessengerBots::setHandlers([TestBotTwoHandler::class]);
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $action = BotAction::factory()->for($bot)->owner($this->tippin)->handler(TestBotTwoHandler::class)->create();
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
