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

        $settings = Messenger::getProviderMessenger();

        $this->assertFalse($settings->message_popups);
        $this->assertFalse($settings->message_sound);
        $this->assertFalse($settings->call_ringtone_sound);
        $this->assertFalse($settings->notify_sound);
        $this->assertFalse($settings->dark_mode);
        $this->assertSame(0, $settings->online_status);
    }

    /** @test */
    public function messenger_settings_sets_offline_cache()
    {
        Cache::put("user:online:{$this->tippin->getKey()}", 'online');

        app(UpdateMessengerSettings::class)->execute([
            'online_status' => 0,
        ]);

        $settings = Messenger::getProviderMessenger();

        $this->assertSame(0, $settings->online_status);

        $this->assertFalse(Cache::has("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function messenger_settings_sets_online_cache()
    {
        app(UpdateMessengerSettings::class)->execute([
            'online_status' => 1,
        ]);

        $settings = Messenger::getProviderMessenger();

        $this->assertSame(1, $settings->online_status);

        $this->assertTrue(Cache::has("user:online:{$this->tippin->getKey()}"));

        $this->assertSame('online', Cache::get("user:online:{$this->tippin->getKey()}"));
    }

    /** @test */
    public function messenger_settings_sets_away_cache()
    {
        app(UpdateMessengerSettings::class)->execute([
            'online_status' => 2,
        ]);

        $settings = Messenger::getProviderMessenger();

        $this->assertSame(2, $settings->online_status);

        $this->assertTrue(Cache::has("user:online:{$this->tippin->getKey()}"));

        $this->assertSame('away', Cache::get("user:online:{$this->tippin->getKey()}"));
    }
}
