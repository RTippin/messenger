<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Bots\UpdateBot;
use RTippin\Messenger\Events\BotUpdatedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
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
            'cooldown' => 0,
        ]);
    }

    /** @test */
    public function it_updates_bot()
    {
        $bot = Bot::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        app(UpdateBot::class)->execute($bot, [
            'name' => 'Renamed',
            'enabled' => false,
            'cooldown' => 99,
        ]);

        $this->assertDatabaseHas('bots', [
            'id' => $bot->id,
            'name' => 'Renamed',
            'enabled' => false,
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
            'cooldown' => 99,
        ]);

        Event::assertDispatched(function (BotUpdatedEvent $event) use ($bot) {
            return $bot->id === $event->bot->id;
        });
    }
}
