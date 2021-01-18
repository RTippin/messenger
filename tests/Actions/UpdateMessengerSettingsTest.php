<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Actions\Messenger\UpdateMessengerSettings;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class UpdateMessengerSettingsTest extends FeatureTestCase
{
    private MessengerProvider $tippin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function messenger_settings_updates()
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
            'owner_type' => get_class($this->tippin),
            'message_popups' => false,
            'message_sound' => false,
            'call_ringtone_sound' => false,
            'notify_sound' => false,
            'dark_mode' => false,
            'online_status' => 0,
        ]);
    }

    /** @test */
    public function messenger_settings_sets_offline_cache()
    {
        Cache::put("user:online:{$this->tippin->getKey()}", 'online');

        app(UpdateMessengerSettings::class)->execute([
            'online_status' => 0,
        ]);

        $this->assertSame(0, Messenger::getProviderMessenger()->online_status);

        $this->assertFalse(Cache::has("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function messenger_settings_sets_online_cache()
    {
        app(UpdateMessengerSettings::class)->execute([
            'online_status' => 1,
        ]);

        $this->assertSame(1, Messenger::getProviderMessenger()->online_status);

        $this->assertTrue(Cache::has("user:online:{$this->tippin->getKey()}"));

        $this->assertSame('online', Cache::get("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function messenger_settings_sets_away_cache()
    {
        app(UpdateMessengerSettings::class)->execute([
            'online_status' => 2,
        ]);

        $this->assertSame(2, Messenger::getProviderMessenger()->online_status);

        $this->assertTrue(Cache::has("user:online:{$this->tippin->getKey()}"));

        $this->assertSame('away', Cache::get("user:online:{$this->tippin->getKey()}"));
    }
}
