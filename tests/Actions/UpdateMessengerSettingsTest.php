<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Actions\Messenger\UpdateMessengerSettings;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class UpdateMessengerSettingsTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_updates_messenger_settings()
    {
        app(UpdateMessengerSettings::class)->execute([
            'message_popups' => false,
            'message_sound' => false,
            'call_ringtone_sound' => false,
            'notify_sound' => false,
            'dark_mode' => false,
            'online_status' => 0,
        ]);

        $this->assertDatabaseHas('messengers', [
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'message_popups' => false,
            'message_sound' => false,
            'call_ringtone_sound' => false,
            'notify_sound' => false,
            'dark_mode' => false,
            'online_status' => 0,
        ]);
    }

    /** @test */
    public function it_removes_online_cache_key_if_offline()
    {
        Cache::put("user:online:{$this->tippin->getKey()}", 'online');

        app(UpdateMessengerSettings::class)->execute([
            'online_status' => 0,
        ]);

        $this->assertFalse(Cache::has("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function it_sets_online_cache_key()
    {
        app(UpdateMessengerSettings::class)->execute([
            'online_status' => 1,
        ]);

        $this->assertTrue(Cache::has("user:online:{$this->tippin->getKey()}"));
        $this->assertSame('online', Cache::get("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function it_sets_away_cache_key()
    {
        app(UpdateMessengerSettings::class)->execute([
            'online_status' => 2,
        ]);

        $this->assertTrue(Cache::has("user:online:{$this->tippin->getKey()}"));
        $this->assertSame('away', Cache::get("user:online:{$this->tippin->getKey()}"));
    }
}
