<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Tests\HttpTestCase;

class StatusHeartbeatTest extends HttpTestCase
{
    /** @test */
    public function messenger_heartbeat_must_be_a_post()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.heartbeat'))
            ->assertStatus(405);
    }

    /** @test */
    public function messenger_heartbeat_online()
    {
        $this->logCurrentRequest();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.heartbeat'), [
            'away' => false,
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function messenger_heartbeat_away()
    {
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.heartbeat'), [
            'away' => true,
        ])
            ->assertSuccessful();
    }

    /**
     * @test
     *
     * @dataProvider awayValidation
     *
     * @param  $awayValue
     */
    public function messenger_heartbeat_checks_boolean($awayValue)
    {
        $this->logCurrentRequest();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.heartbeat'), [
            'away' => $awayValue,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('away');
    }

    public static function awayValidation(): array
    {
        return [
            'Away cannot be empty' => [''],
            'Away cannot be string' => ['string'],
            'Away cannot be integers' => [5],
            'Away cannot be null' => [null],
            'Away cannot be an array' => [[1, 2]],
        ];
    }
}
