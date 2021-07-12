<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Tests\Fixtures\OtherModel;
use RTippin\Messenger\Tests\HttpTestCase;

class ProviderChannelTest extends HttpTestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        // Need to set a driver other than null
        // for broadcast routes to be utilized
        $app->get('config')->set('broadcasting.default', 'redis');
    }

    /** @test */
    public function guest_is_unauthorized()
    {
        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'private-messenger.user.1',
        ])
            ->assertUnauthorized();
    }

    /** @test */
    public function invalid_alias_forbidden()
    {
        $this->actingAs($this->tippin);

        $this->postJson('api/broadcasting/auth', [
            'channel_name' => "private-messenger.unknown.{$this->tippin->getKey()}",
        ])
            ->assertForbidden();
    }

    /** @test */
    public function invalid_id_forbidden()
    {
        $this->actingAs($this->tippin);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'private-messenger.user.404',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function null_is_forbidden()
    {
        $this->actingAs($this->tippin);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'private-messenger.',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function invalid_provider_is_forbidden()
    {
        $this->actingAs(new OtherModel([
            'id' => 404,
        ]));

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'private-messenger.user.404',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function user_is_authorized()
    {
        $this->actingAs($this->tippin);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "private-messenger.user.{$this->tippin->getKey()}",
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function company_is_authorized()
    {
        $this->actingAs($this->developers);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "private-messenger.company.{$this->developers->getKey()}",
        ])
            ->assertSuccessful();
    }
}
