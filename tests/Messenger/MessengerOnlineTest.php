<?php

namespace RTippin\Messenger\Tests\Messenger;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class MessengerOnlineTest extends FeatureTestCase
{
    private Messenger $messenger;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->messenger = app(Messenger::class);
    }

    /** @test */
    public function messenger_sets_current_provider_online_cache_key()
    {
        $this->messenger->setProvider($this->tippin);

        $this->messenger->setProviderToOnline();

        $this->assertTrue(Cache::has("user:online:{$this->tippin->getKey()}"));
        $this->assertSame('online', Cache::get("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function messenger_sets_given_provider_online_cache_key()
    {
        $this->messenger->setProviderToOnline($this->doe);

        $this->assertTrue(Cache::has("user:online:{$this->doe->getKey()}"));
        $this->assertSame('online', Cache::get("user:online:{$this->doe->getKey()}"));
    }

    /** @test */
    public function messenger_sets_away_cache_key_when_online_called_if_settings_set_to_away()
    {
        DB::table('messengers')->update([
            'online_status' => 2,
        ]);

        $this->messenger->setProviderToOnline($this->tippin);

        $this->assertTrue(Cache::has("user:online:{$this->tippin->getKey()}"));
        $this->assertSame('away', Cache::get("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function messenger_doesnt_set_online_cache_key_when_provider_settings_offline()
    {
        DB::table('messengers')->update([
            'online_status' => 0,
        ]);

        $this->messenger->setProviderToOnline($this->tippin);

        $this->assertFalse(Cache::has("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function messenger_doesnt_set_online_cache_key_when_disabled_from_config()
    {
        $this->messenger->setOnlineStatus(false);

        $this->messenger->setProviderToOnline($this->tippin);

        $this->assertFalse(Cache::has("user:online:{$this->tippin->getKey()}"));
    }
}
