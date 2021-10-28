<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Bots\UpdateBotAction;
use RTippin\Messenger\Events\BotActionUpdatedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class UpdateBotActionTest extends FeatureTestCase
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
        $action = BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Bots are currently disabled.');

        app(UpdateBotAction::class)->execute($action, []);
    }

    /** @test */
    public function it_updates_bot_action_and_clears_actions_cache()
    {
        $action = BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();
        $cache = Cache::spy();

        app(UpdateBotAction::class)->execute($action, [
            'match' => 'test',
            'triggers' => 'test',
            'admin_only' => true,
            'cooldown' => 99,
            'enabled' => false,
            'payload' => '{"test":true}',
        ]);

        $cache->shouldHaveReceived('forget');
        $this->assertDatabaseHas('bot_actions', [
            'id' => $action->id,
            'match' => 'test',
            'triggers' => 'test',
            'admin_only' => true,
            'cooldown' => 99,
            'enabled' => false,
            'payload' => '{"test":true}',
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        $action = BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();
        Event::fake([
            BotActionUpdatedEvent::class,
        ]);

        app(UpdateBotAction::class)->execute($action, [
            'match' => 'test',
            'triggers' => 'test',
            'admin_only' => true,
            'cooldown' => 99,
            'enabled' => false,
            'payload' => '{"test":true}',
        ]);

        Event::assertDispatched(function (BotActionUpdatedEvent $event) use ($action) {
            return $action->id === $event->action->id;
        });
    }

    /** @test */
    public function it_doesnt_fire_events_or_clear_actions_cache_when_not_changed()
    {
        BaseMessengerAction::enableEvents();
        $action = BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();
        Event::fake([
            BotActionUpdatedEvent::class,
        ]);

        app(UpdateBotAction::class)->execute($action, [
            'match' => 'exact',
            'triggers' => '!hello',
            'admin_only' => false,
            'cooldown' => 0,
            'enabled' => true,
            'payload' => null,
        ]);

        Event::assertNotDispatched(BotActionUpdatedEvent::class);
    }
}
