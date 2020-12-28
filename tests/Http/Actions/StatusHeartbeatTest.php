<?php

namespace RTippin\Messenger\Tests\Http\Actions;

use RTippin\Messenger\Events\StatusHeartbeatEvent;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\UserModel;

class StatusHeartbeatTest extends FeatureTestCase
{
    /** @test */
    public function messenger_heartbeat_online()
    {
        $this->expectsEvents([
            StatusHeartbeatEvent::class,
        ]);

        $user = UserModel::first();

        $this->actingAs($user);

        $this->postJson(route('api.messenger.heartbeat'), [
            'away' => false,
        ])
            ->assertSuccessful();

        $this->assertEquals(1, $user->onlineStatus());
    }

    /** @test */
    public function messenger_heartbeat_away()
    {
        $this->expectsEvents([
            StatusHeartbeatEvent::class,
        ]);

        $user = UserModel::first();

        $this->actingAs($user);

        $this->postJson(route('api.messenger.heartbeat'), [
            'away' => true,
        ])
            ->assertSuccessful();

        $this->assertEquals(2, $user->onlineStatus());
    }
}