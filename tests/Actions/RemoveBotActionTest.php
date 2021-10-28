<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Bots\RemoveBotAction;
use RTippin\Messenger\Events\BotActionRemovedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class RemoveBotActionTest extends FeatureTestCase
{
    /** @test */
    public function it_throws_exception_if_disabled()
    {
        Messenger::setBots(false);
        $action = BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Bots are currently disabled.');

        app(RemoveBotAction::class)->execute($action);
    }

    /** @test */
    public function it_removes_action_and_clears_actions_cache()
    {
        $action = BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();
        $cache = Cache::spy();

        app(RemoveBotAction::class)->execute($action);

        $cache->shouldHaveReceived('forget');
        $this->assertDatabaseMissing('bot_actions', [
            'id' => $action->id,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            BotActionRemovedEvent::class,
        ]);
        $action = BotAction::factory()->for(
            Bot::factory()->for(
                Thread::factory()->group()->create()
            )->owner($this->tippin)->create()
        )->owner($this->tippin)->create();

        app(RemoveBotAction::class)->execute($action);

        Event::assertDispatched(function (BotActionRemovedEvent $event) use ($action) {
            return $action->id === $event->action['id'];
        });
    }
}
