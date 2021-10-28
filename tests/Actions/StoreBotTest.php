<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Bots\StoreBot;
use RTippin\Messenger\Events\NewBotEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\BotAddedMessage;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreBotTest extends FeatureTestCase
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

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Bots are currently disabled.');

        app(StoreBot::class)->execute($thread, [
            'name' => 'Test Bot',
            'enabled' => true,
            'hide_actions' => false,
            'cooldown' => 0,
        ]);
    }

    /** @test */
    public function it_stores_bot_and_clears_actions_cache()
    {
        $thread = Thread::factory()->group()->create();
        $cache = Cache::spy();

        app(StoreBot::class)->execute($thread, [
            'name' => 'Test Bot',
            'enabled' => true,
            'hide_actions' => true,
            'cooldown' => 0,
        ]);

        $cache->shouldHaveReceived('forget');
        $this->assertDatabaseHas('bots', [
            'thread_id' => $thread->id,
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'name' => 'Test Bot',
            'enabled' => true,
            'hide_actions' => true,
            'cooldown' => 0,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        $thread = Thread::factory()->group()->create();
        Event::fake([
            NewBotEvent::class,
        ]);

        app(StoreBot::class)->execute($thread, [
            'name' => 'Test Bot',
            'enabled' => true,
            'hide_actions' => false,
            'cooldown' => 0,
        ]);

        Event::assertDispatched(function (NewBotEvent $event) use ($thread) {
            return $thread->id === $event->bot->thread_id;
        });
    }

    /** @test */
    public function it_dispatches_subscriber_job()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        $thread = Thread::factory()->group()->create();

        app(StoreBot::class)->execute($thread, [
            'name' => 'Test Bot',
            'enabled' => true,
            'hide_actions' => false,
            'cooldown' => 0,
        ]);

        Bus::assertDispatched(BotAddedMessage::class);
    }

    /** @test */
    public function it_runs_subscriber_job_now()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('queued', false);
        $thread = Thread::factory()->group()->create();

        app(StoreBot::class)->execute($thread, [
            'name' => 'Test Bot',
            'enabled' => true,
            'hide_actions' => false,
            'cooldown' => 0,
        ]);

        Bus::assertDispatchedSync(BotAddedMessage::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_job_if_disabled()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('enabled', false);
        $thread = Thread::factory()->group()->create();

        app(StoreBot::class)->execute($thread, [
            'name' => 'Test Bot',
            'enabled' => true,
            'hide_actions' => false,
            'cooldown' => 0,
        ]);

        Bus::assertNotDispatched(BotAddedMessage::class);
    }
}
