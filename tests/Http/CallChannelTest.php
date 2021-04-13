<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallChannelTest extends FeatureTestCase
{
    private Thread $private;
    private Call $call;

    protected function setUp(): void
    {
        parent::setUp();

        $this->private = $this->createPrivateThread($this->tippin, $this->doe);
        $this->call = $this->createCall($this->private, $this->tippin);
    }

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
            'channel_name' => "presence-messenger.call.{$this->call->id}.thread.{$this->private->id}",
        ])
            ->assertUnauthorized();
    }

    /** @test */
    public function missing_thread_forbidden()
    {
        $this->actingAs($this->tippin);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.call.{$this->call->id}.thread.404",
        ])
            ->assertForbidden();
    }

    /** @test */
    public function missing_call_forbidden()
    {
        $this->actingAs($this->tippin);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.call.404.thread.{$this->private->id}",
        ])
            ->assertForbidden();
    }

    /** @test */
    public function non_participant_forbidden()
    {
        $this->actingAs($this->companyDevelopers());

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.call.{$this->call->id}.thread.{$this->private->id}",
        ])
            ->assertForbidden();
    }

    /** @test */
    public function non_joined_participant_forbidden()
    {
        $this->actingAs($this->doe);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.call.{$this->call->id}.thread.{$this->private->id}",
        ])
            ->assertForbidden();
    }

    /** @test */
    public function kicked_participant_forbidden()
    {
        $this->call->participants()
            ->first()
            ->update([
                'kicked' => true,
            ]);
        $this->actingAs($this->tippin);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.call.{$this->call->id}.thread.{$this->private->id}",
        ])
            ->assertForbidden();
    }

    /** @test */
    public function inactive_call_forbidden()
    {
        $this->call->update([
            'call_ended' => now(),
        ]);
        $this->actingAs($this->tippin);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.call.{$this->call->id}.thread.{$this->private->id}",
        ])
            ->assertForbidden();
    }

    /** @test */
    public function call_participant_is_authorized()
    {
        $this->actingAs($this->tippin);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.call.{$this->call->id}.thread.{$this->private->id}",
        ])
            ->assertSuccessful()
            ->assertJson([
                'channel_data' => [
                    'user_info' => [
                        'name' => 'Richard Tippin',
                        'provider_id' => $this->tippin->getKey(),
                    ],
                ],
            ]);
    }
}
