<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Tests\FeatureTestCase;

class MessengerSettingsTest extends FeatureTestCase
{
    /** @test */
    public function messenger_created_when_called_from_user_without_messenger()
    {
        $jane = $this->createJaneSmith();

        $this->assertDatabaseMissing('messengers', [
            'owner_id' => $jane->getKey(),
        ]);

        $this->actingAs($jane);

        $this->getJson(route('api.messenger.settings'))
            ->assertSuccessful()
            ->assertJson([
                'owner_id' => $jane->getKey(),
                'dark_mode' => true,
            ]);

        $this->assertDatabaseHas('messengers', [
            'owner_id' => $jane->getKey(),
        ]);
    }

    /** @test */
    public function updating_messenger_settings_and_set_status_to_away()
    {
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.settings'), [
            'message_popups' => false,
            'message_sound' => false,
            'call_ringtone_sound' => false,
            'notify_sound' => false,
            'dark_mode' => false,
            'online_status' => 2,
        ])
            ->assertSuccessful()
            ->assertJson([
                'message_popups' => false,
                'message_sound' => false,
                'call_ringtone_sound' => false,
                'notify_sound' => false,
                'dark_mode' => false,
                'online_status' => 2,
            ]);

        $this->assertSame(2, $this->tippin->getProviderOnlineStatus());
    }

    /** @test */
    public function updating_messenger_settings_and_set_status_to_online()
    {
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.settings'), [
            'message_popups' => true,
            'message_sound' => true,
            'call_ringtone_sound' => true,
            'notify_sound' => true,
            'dark_mode' => true,
            'online_status' => 1,
        ])
            ->assertSuccessful()
            ->assertJson([
                'message_popups' => true,
                'message_sound' => true,
                'call_ringtone_sound' => true,
                'notify_sound' => true,
                'dark_mode' => true,
                'online_status' => 1,
            ]);

        $this->assertSame(1, $this->tippin->getProviderOnlineStatus());
    }

    /** @test */
    public function updating_messenger_settings_and_set_status_to_offline()
    {
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.settings'), [
            'message_popups' => true,
            'message_sound' => true,
            'call_ringtone_sound' => true,
            'notify_sound' => true,
            'dark_mode' => true,
            'online_status' => 0,
        ])
            ->assertSuccessful()
            ->assertJson([
                'message_popups' => true,
                'message_sound' => true,
                'call_ringtone_sound' => true,
                'notify_sound' => true,
                'dark_mode' => true,
                'online_status' => 0,
            ]);

        $this->assertSame(0, $this->tippin->getProviderOnlineStatus());
    }

    /**
     * @test
     * @dataProvider settingsValidation
     * @param $boolInput
     * @param $intInput
     */
    public function updating_messenger_settings_checks_booleans_and_integer($boolInput, $intInput)
    {
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.settings'), [
            'message_popups' => $boolInput,
            'message_sound' => $boolInput,
            'call_ringtone_sound' => $boolInput,
            'notify_sound' => $boolInput,
            'dark_mode' => $boolInput,
            'online_status' => $intInput,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'message_popups',
                'message_sound',
                'call_ringtone_sound',
                'notify_sound',
                'dark_mode',
                'online_status',
            ]);
    }

    public function settingsValidation(): array
    {
        return [
            'Toggle cannot be int and status cannot be null' => [2, null],
            'Toggle cannot be string and status cannot be greater than 2' => ['string', 3],
            'Toggle cannot be array and status cannot be string' => [[], 'string'],
            'Toggle and status cannot be null' => [null, null],
            'Toggle and status cannot be empty' => ['', ''],
        ];
    }
}
