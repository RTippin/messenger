<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Tests\HttpTestCase;

class MessengerSettingsTest extends HttpTestCase
{
    /** @test */
    public function user_can_view_messenger_settings()
    {
        $this->logCurrentRequest();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.settings'))
            ->assertSuccessful()
            ->assertJson([
                'owner_id' => $this->tippin->getKey(),
            ]);
    }

    /** @test */
    public function user_can_update_messenger_settings()
    {
        $this->logCurrentRequest();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.settings.update'), [
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
    }

    /**
     * @test
     *
     * @dataProvider settingsValidation
     *
     * @param  $boolInput
     * @param  $intInput
     */
    public function updating_messenger_settings_checks_booleans_and_integer($boolInput, $intInput)
    {
        $this->logCurrentRequest();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.settings.update'), [
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

    public static function settingsValidation(): array
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
