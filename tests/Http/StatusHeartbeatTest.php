<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Events\StatusHeartbeatEvent;
use RTippin\Messenger\Tests\FeatureTestCase;

class StatusHeartbeatTest extends FeatureTestCase
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
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            StatusHeartbeatEvent::class,
        ]);

        $this->postJson(route('api.messenger.heartbeat'), [
            'away' => false,
        ])
            ->assertSuccessful();

        $this->assertSame(1, $this->tippin->getProviderOnlineStatus());
    }

    /** @test */
    public function messenger_heartbeat_away()
    {
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            StatusHeartbeatEvent::class,
        ]);

        $this->postJson(route('api.messenger.heartbeat'), [
            'away' => true,
        ])
            ->assertSuccessful();

        $this->assertSame(2, $this->tippin->getProviderOnlineStatus());
    }

    /**
     * @test
     * @dataProvider awayValidation
     * @param $awayValue
     */
    public function messenger_heartbeat_checks_boolean($awayValue)
    {
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.heartbeat'), [
            'away' => $awayValue,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('away');
    }

    public function awayValidation(): array
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
