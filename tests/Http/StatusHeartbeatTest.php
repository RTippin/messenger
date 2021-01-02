<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Events\StatusHeartbeatEvent;
use RTippin\Messenger\Tests\FeatureTestCase;

class StatusHeartbeatTest extends FeatureTestCase
{
    /** @test */
    public function messenger_heartbeat_must_be_a_post()
    {
        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.heartbeat'))
            ->assertStatus(405);
    }

    /**
     * @test
     * @dataProvider awayValidation
     * @param $awayValue
     */
    public function messenger_heartbeat_checks_boolean($awayValue)
    {
        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.heartbeat'), [
            'away' => $awayValue,
        ])
            ->assertJsonValidationErrors('away');
    }

    /** @test */
    public function messenger_heartbeat_online()
    {
        Event::fake([
            StatusHeartbeatEvent::class,
        ]);

        $tippin = $this->userTippin();

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.heartbeat'), [
            'away' => false,
        ])
            ->assertSuccessful();

        $this->assertEquals(1, $tippin->onlineStatus());

        Event::assertDispatched(function (StatusHeartbeatEvent $event) use ($tippin) {
            $this->assertEquals($tippin->getKey(), $event->provider->getKey());
            $this->assertFalse($event->away);
            $this->assertNotNull($event->IP);

            return true;
        });
    }

    /** @test */
    public function messenger_heartbeat_away()
    {
        Event::fake([
            StatusHeartbeatEvent::class,
        ]);

        $tippin = $this->userTippin();

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.heartbeat'), [
            'away' => true,
        ])
            ->assertSuccessful();

        $this->assertEquals(2, $tippin->onlineStatus());

        Event::assertDispatched(function (StatusHeartbeatEvent $event) use ($tippin) {
            $this->assertEquals($tippin->getKey(), $event->provider->getKey());
            $this->assertTrue($event->away);
            $this->assertNotNull($event->IP);

            return true;
        });
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
