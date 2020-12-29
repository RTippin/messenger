<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Events\StatusHeartbeatEvent;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\stubs\UserModel;

class StatusHeartbeatTest extends FeatureTestCase
{
    /** @test */
    public function messenger_heartbeat_must_be_a_post()
    {
        $this->doesntExpectEvents([
            StatusHeartbeatEvent::class,
        ]);

        $this->actingAs(UserModel::find(1));

        $this->getJson(route('api.messenger.heartbeat'))
            ->assertStatus(405);
    }

    /** @test */
    public function messenger_heartbeat_validates_input()
    {
        $this->doesntExpectEvents([
            StatusHeartbeatEvent::class,
        ]);

        $this->actingAs(UserModel::find(1));

        $this->postJson(route('api.messenger.heartbeat'), [
            'away' => 'string',
        ])
            ->assertJsonValidationErrors('away');

        $this->postJson(route('api.messenger.heartbeat'))
            ->assertJsonValidationErrors('away');
    }

    /** @test */
    public function messenger_heartbeat_online()
    {
        Event::fake([
            StatusHeartbeatEvent::class,
        ]);

        $user = UserModel::find(1);

        $this->actingAs($user);

        $this->postJson(route('api.messenger.heartbeat'), [
            'away' => false,
        ])
            ->assertSuccessful();

        $this->assertEquals(1, $user->onlineStatus());

        Event::assertDispatched(function (StatusHeartbeatEvent $event) {
            $this->assertEquals(1, $event->provider->getKey());
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

        $user = UserModel::find(1);

        $this->actingAs($user);

        $this->postJson(route('api.messenger.heartbeat'), [
            'away' => true,
        ])
            ->assertSuccessful();

        $this->assertEquals(2, $user->onlineStatus());

        Event::assertDispatched(function (StatusHeartbeatEvent $event) {
            $this->assertEquals(1, $event->provider->getKey());
            $this->assertTrue($event->away);
            $this->assertNotNull($event->IP);

            return true;
        });
    }
}
