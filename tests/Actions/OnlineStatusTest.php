<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Messenger\OnlineStatus;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\StatusHeartbeatEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class OnlineStatusTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_stores_away_cache_key()
    {
        app(OnlineStatus::class)->execute(true);

        $this->assertTrue(Cache::has("user:online:{$this->tippin->getKey()}"));
        $this->assertSame('away', Cache::get("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function it_stores_online_cache_key()
    {
        app(OnlineStatus::class)->execute(false);

        $this->assertTrue(Cache::has("user:online:{$this->tippin->getKey()}"));
        $this->assertSame('online', Cache::get("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function it_doesnt_store_cache_keys_if_disabled()
    {
        Messenger::setOnlineStatus(false);

        app(OnlineStatus::class)->execute(false);

        $this->assertFalse(Cache::has("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function it_doesnt_touch_provider_if_provider_set_to_offline()
    {
        Carbon::setTestNow($before = now()->subMinutes(5));
        $this->tippin->update([
            'updated_at' => $before,
        ]);
        Messenger::getProviderMessenger()->update([
            'online_status' => MessengerProvider::OFFLINE,
        ]);

        app(OnlineStatus::class)->execute(false);

        $this->assertDatabaseHas('users', [
            'id' => $this->tippin->getKey(),
            'updated_at' => $before,
        ]);
    }

    /** @test */
    public function it_touches_provider()
    {
        $before = now()->subMinutes(5);
        $this->tippin->update([
            'updated_at' => $before,
        ]);

        app(OnlineStatus::class)->execute(true);

        $this->assertNotSame($before->toDayDateTimeString(), $this->tippin->updated_at->toDayDateTimeString());
    }

    /** @test */
    public function it_fires_away_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            StatusHeartbeatEvent::class,
        ]);

        app(OnlineStatus::class)->execute(true);

        Event::assertDispatched(function (StatusHeartbeatEvent $event) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertTrue($event->away);
            $this->assertSame('127.0.0.1', $event->IP);

            return true;
        });
    }

    /** @test */
    public function it_fires_online_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            StatusHeartbeatEvent::class,
        ]);

        app(OnlineStatus::class)->execute(false);

        Event::assertDispatched(function (StatusHeartbeatEvent $event) {
            return $event->away === false;
        });
    }
}
