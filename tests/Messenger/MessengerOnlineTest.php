<?php

namespace RTippin\Messenger\Tests\Messenger;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\OtherModel;

class MessengerOnlineTest extends FeatureTestCase
{
    private Messenger $messenger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messenger = app(Messenger::class);
    }

    /** @test */
    public function it_sets_current_provider_online_cache_key()
    {
        $this->messenger->setProvider($this->tippin);

        $this->messenger->setProviderToOnline();

        $this->assertTrue(Cache::has("user:online:{$this->tippin->getKey()}"));
        $this->assertSame('online', Cache::get("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function it_sets_given_provider_online_cache_key()
    {
        $this->messenger->setProviderToOnline($this->doe);

        $this->assertTrue(Cache::has("user:online:{$this->doe->getKey()}"));
        $this->assertSame('online', Cache::get("user:online:{$this->doe->getKey()}"));
    }

    /** @test */
    public function it_sets_away_cache_key_if_settings_set_to_away()
    {
        DB::table('messengers')->update([
            'online_status' => 2,
        ]);

        $this->messenger->setProviderToOnline($this->tippin);

        $this->assertTrue(Cache::has("user:online:{$this->tippin->getKey()}"));
        $this->assertSame('away', Cache::get("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function it_doesnt_set_online_cache_key_if_settings_offline()
    {
        DB::table('messengers')->update([
            'online_status' => 0,
        ]);

        $this->messenger->setProviderToOnline($this->tippin);

        $this->assertFalse(Cache::has("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function it_doesnt_set_online_cache_key_if_disabled()
    {
        $this->messenger->setOnlineStatus(false);

        $this->messenger->setProviderToOnline($this->tippin);

        $this->assertFalse(Cache::has("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function it_doesnt_set_online_cache_key_if_invalid_provider()
    {
        $invalid = new OtherModel([
            'id' => 404,
        ]);

        $this->messenger->setProviderToOnline($invalid);

        $this->assertFalse(Cache::has("user:online:{$invalid->getKey()}"));
    }

    /** @test */
    public function it_removes_current_provider_online_cache_key()
    {
        Cache::put("user:online:{$this->tippin->getKey()}", 'online');
        $this->messenger->setProvider($this->tippin);

        $this->messenger->setProviderToOffline();

        $this->assertFalse(Cache::has("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function it_removes_given_provider_online_cache_key()
    {
        Cache::put("user:online:{$this->doe->getKey()}", 'online');

        $this->messenger->setProviderToOffline($this->doe);

        $this->assertFalse(Cache::has("user:online:{$this->doe->getKey()}"));
    }

    /** @test */
    public function it_sets_current_provider_away_cache_key()
    {
        $this->messenger->setProvider($this->tippin);

        $this->messenger->setProviderToAway();

        $this->assertTrue(Cache::has("user:online:{$this->tippin->getKey()}"));
        $this->assertSame('away', Cache::get("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function it_sets_given_provider_away_cache_key()
    {
        $this->messenger->setProviderToAway($this->doe);

        $this->assertTrue(Cache::has("user:online:{$this->doe->getKey()}"));
        $this->assertSame('away', Cache::get("user:online:{$this->doe->getKey()}"));
    }

    /** @test */
    public function it_doesnt_set_away_cache_key_if_settings_offline()
    {
        DB::table('messengers')->update([
            'online_status' => 0,
        ]);

        $this->messenger->setProviderToAway($this->tippin);

        $this->assertFalse(Cache::has("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function it_doesnt_set_away_cache_key_if_disabled()
    {
        $this->messenger->setOnlineStatus(false);

        $this->messenger->setProviderToAway($this->tippin);

        $this->assertFalse(Cache::has("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function it_doesnt_set_away_cache_key_for_invalid_provider()
    {
        $this->messenger->setProviderToAway(new OtherModel([
            'id' => 404,
        ]));

        $this->assertFalse(Cache::has('user:online:404'));
    }

    /** @test */
    public function it_returns_set_provider_is_online()
    {
        Cache::put("user:online:{$this->tippin->getKey()}", 'online');

        $this->messenger->setProvider($this->tippin);

        $this->assertTrue($this->messenger->isProviderOnline());
    }

    /** @test */
    public function it_returns_given_provider_is_online()
    {
        Cache::put("user:online:{$this->doe->getKey()}", 'online');

        $this->assertTrue($this->messenger->isProviderOnline($this->doe));
    }

    /** @test */
    public function it_returns_provider_is_not_online_if_disabled()
    {
        $this->messenger->setOnlineStatus(false);
        Cache::put("user:online:{$this->tippin->getKey()}", 'online');

        $this->assertFalse($this->messenger->isProviderOnline($this->tippin));
    }

    /** @test */
    public function it_returns_invalid_provider_is_not_online()
    {
        $invalid = new OtherModel([
            'id' => 404,
        ]);
        Cache::put('user:online:404', 'online');

        $this->assertFalse($this->messenger->isProviderOnline($invalid));
    }

    /** @test */
    public function it_returns_set_provider_is_away()
    {
        Cache::put("user:online:{$this->tippin->getKey()}", 'away');
        $this->messenger->setProvider($this->tippin);

        $this->assertTrue($this->messenger->isProviderAway());
    }

    /** @test */
    public function it_returns_given_provider_is_away()
    {
        Cache::put("user:online:{$this->doe->getKey()}", 'away');

        $this->assertTrue($this->messenger->isProviderAway($this->doe));
    }

    /** @test */
    public function it_returns_provider_is_not_away_if_online()
    {
        Cache::put("user:online:{$this->doe->getKey()}", 'online');

        $this->assertFalse($this->messenger->isProviderAway($this->doe));
    }

    /** @test */
    public function it_returns_provider_is_not_away_if_disabled()
    {
        $this->messenger->setOnlineStatus(false);
        Cache::put("user:online:{$this->tippin->getKey()}", 'away');

        $this->assertFalse($this->messenger->isProviderAway($this->tippin));
    }

    /** @test */
    public function it_returns_invalid_provider_is_not_away()
    {
        $invalid = new OtherModel([
            'id' => 404,
        ]);
        Cache::put('user:online:404', 'away');

        $this->assertFalse($this->messenger->isProviderAway($invalid));
    }

    /** @test */
    public function it_returns_online_status_number_for_set_provider()
    {
        $this->messenger->setProvider($this->tippin);

        $this->assertSame(MessengerProvider::OFFLINE, $this->messenger->getProviderOnlineStatus());

        Cache::put("user:online:{$this->tippin->getKey()}", 'online');

        $this->assertSame(MessengerProvider::ONLINE, $this->messenger->getProviderOnlineStatus());

        Cache::put("user:online:{$this->tippin->getKey()}", 'away');

        $this->assertSame(MessengerProvider::AWAY, $this->messenger->getProviderOnlineStatus());
    }

    /** @test */
    public function it_returns_online_status_number_for_given_provider()
    {
        $this->assertSame(MessengerProvider::OFFLINE, $this->messenger->getProviderOnlineStatus($this->doe));

        Cache::put("user:online:{$this->doe->getKey()}", 'online');

        $this->assertSame(MessengerProvider::ONLINE, $this->messenger->getProviderOnlineStatus($this->doe));

        Cache::put("user:online:{$this->doe->getKey()}", 'away');

        $this->assertSame(MessengerProvider::AWAY, $this->messenger->getProviderOnlineStatus($this->doe));
    }

    /** @test */
    public function it_returns_online_status_number_for_invalid_provider_as_offline()
    {
        $invalid = new OtherModel([
            'id' => 404,
        ]);

        $this->assertSame(MessengerProvider::OFFLINE, $this->messenger->getProviderOnlineStatus($invalid));

        Cache::put("user:online:{$invalid->getKey()}", 'online');

        $this->assertSame(MessengerProvider::OFFLINE, $this->messenger->getProviderOnlineStatus($invalid));

        Cache::put("user:online:{$invalid->getKey()}", 'away');

        $this->assertSame(MessengerProvider::OFFLINE, $this->messenger->getProviderOnlineStatus($invalid));
    }
}
