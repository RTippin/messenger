<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class ThreadChannelTest extends FeatureTestCase
{
    private Thread $private;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->private = $this->createPrivateThread($this->tippin, $this->doe);
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $config = $app->get('config');

        // Need to set a driver other than null
        // for broadcast routes to be utilized
        $config->set('broadcasting.default', 'redis');
    }

    /** @test */
    public function guest_is_unauthorized()
    {
        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.thread.{$this->private->id}",
        ])
            ->assertUnauthorized();
    }

    /** @test */
    public function missing_thread_forbidden()
    {
        $this->actingAs($this->tippin);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => 'presence-messenger.thread.404',
        ])
            ->assertForbidden();
    }

    /** @test */
    public function non_participant_forbidden()
    {
        $this->actingAs($this->companyDevelopers());

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.thread.{$this->private->id}",
        ])
            ->assertForbidden();
    }

    /** @test */
    public function pending_participant_forbidden()
    {
        $this->private->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first()
            ->update([
                'pending' => true,
            ]);

        $this->actingAs($this->doe);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.thread.{$this->private->id}",
        ])
            ->assertForbidden();
    }

    /** @test */
    public function participant_is_authorized()
    {
        $this->actingAs($this->tippin);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.thread.{$this->private->id}",
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
