<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Bots\ArchiveBot;
use RTippin\Messenger\Events\BotArchivedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\BotRemovedMessage;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class ArchiveBotTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_throws_exception_if_disabled()
    {
        Messenger::setBots(false);
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Bots are currently disabled.');

        app(ArchiveBot::class)->execute($bot);
    }

    /** @test */
    public function it_soft_deletes_bot()
    {
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        app(ArchiveBot::class)->execute($bot);

        $this->assertSoftDeleted('bots', [
            'id' => $bot->id,
        ]);
    }

    /** @test */
    public function it_clears_actions_cache()
    {
        $thread = Thread::factory()->group()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $cache = Cache::spy();

        app(ArchiveBot::class)->execute($bot);

        $cache->shouldHaveReceived('forget');
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            BotArchivedEvent::class,
        ]);
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        app(ArchiveBot::class)->execute($bot);

        Event::assertDispatched(function (BotArchivedEvent $event) use ($bot) {
            return $bot->id === $event->bot->id;
        });
    }

    /** @test */
    public function it_dispatches_subscriber_job()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        app(ArchiveBot::class)->execute($bot);

        Bus::assertDispatched(BotRemovedMessage::class);
    }

    /** @test */
    public function it_runs_subscriber_job_now()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('queued', false);
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        app(ArchiveBot::class)->execute($bot);

        Bus::assertDispatchedSync(BotRemovedMessage::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_job_if_disabled()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('enabled', false);
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        app(ArchiveBot::class)->execute($bot);

        Bus::assertNotDispatched(BotRemovedMessage::class);
    }
}
