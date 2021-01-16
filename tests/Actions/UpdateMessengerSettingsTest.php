<?php

namespace RTippin\Messenger\Tests\Actions;

use RTippin\Messenger\Actions\UpdateMessengerSettings;
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
        app(UpdateMessengerSettings::class)
            ->execute([
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
}
