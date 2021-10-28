<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Bots\UpdateBot;
use RTippin\Messenger\Events\BotUpdatedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\BotNameMessage;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class UpdateBotTest extends FeatureTestCase
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
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Bots are currently disabled.');

        app(UpdateBot::class)->execute($bot, [
            'name' => 'Test Bot',
            'enabled' => true,
            'hide_actions' => true,
            'cooldown' => 0,
        ]);
    }

    /** @test */
    public function it_updates_bot_and_clears_actions_cache()
    {
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();
        $cache = Cache::spy();

        app(UpdateBot::class)->execute($bot, [
            'name' => 'Renamed',
            'enabled' => false,
            'hide_actions' => false,
            'cooldown' => 99,
        ]);

        $cache->shouldHaveReceived('forget');
        $this->assertDatabaseHas('bots', [
            'id' => $bot->id,
            'name' => 'Renamed',
            'enabled' => false,
            'hide_actions' => false,
            'cooldown' => 99,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();
        Event::fake([
            BotUpdatedEvent::class,
        ]);

        app(UpdateBot::class)->execute($bot, [
            'name' => 'Renamed',
            'enabled' => false,
            'hide_actions' => false,
            'cooldown' => 99,
        ]);

        Event::assertDispatched(function (BotUpdatedEvent $event) use ($bot) {
            return $bot->id === $event->bot->id;
        });
    }

    /** @test */
    public function it_doesnt_fire_events_or_clear_actions_cache_if_not_updated()
    {
        BaseMessengerAction::enableEvents();
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create([
            'name' => 'Test Bot',
            'enabled' => true,
            'cooldown' => 0,
            'hide_actions' => false,
        ]);
        $cache = Cache::spy();
        Event::fake([
            BotUpdatedEvent::class,
        ]);

        app(UpdateBot::class)->execute($bot, [
            'name' => 'Test Bot',
            'enabled' => true,
            'cooldown' => 0,
            'hide_actions' => false,
        ]);

        $cache->shouldNotHaveReceived('forget');
        Event::assertNotDispatched(BotUpdatedEvent::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_job_if_name_did_not_change()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create([
            'name' => 'Test Bot',
            'enabled' => true,
            'cooldown' => 0,
            'hide_actions' => false,
        ]);

        app(UpdateBot::class)->execute($bot, [
            'name' => 'Test Bot',
            'enabled' => true,
            'cooldown' => 0,
            'hide_actions' => true,
        ]);

        Bus::assertNotDispatched(BotNameMessage::class);
    }

    /** @test */
    public function it_dispatches_subscriber_job()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create([
            'name' => 'Test Bot',
        ]);

        app(UpdateBot::class)->execute($bot, [
            'name' => 'Renamed Bot',
        ]);

        Bus::assertDispatched(BotNameMessage::class);
    }

    /** @test */
    public function it_runs_subscriber_job_now()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('queued', false);
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create([
            'name' => 'Test Bot',
        ]);

        app(UpdateBot::class)->execute($bot, [
            'name' => 'Renamed Bot',
        ]);

        Bus::assertDispatchedSync(BotNameMessage::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_job_if_disabled()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('enabled', false);
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create([
            'name' => 'Test Bot',
        ]);

        app(UpdateBot::class)->execute($bot, [
            'name' => 'Renamed Bot',
        ]);

        Bus::assertNotDispatched(BotNameMessage::class);
    }
}
