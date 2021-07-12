<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class CallChannelTest extends HttpTestCase
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
            'channel_name' => 'presence-messenger.call.1234.thread.5678',
        ])
            ->assertUnauthorized();
    }

    /** @test */
    public function missing_thread_forbidden()
    {
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.call.$call->id.thread.404",
        ])
            ->assertForbidden();
    }

    /** @test */
    public function missing_call_forbidden()
    {
        $thread = Thread::factory()->create();
        $this->actingAs($this->tippin);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.call.404.thread.$thread->id",
        ])
            ->assertForbidden();
    }

    /** @test */
    public function non_thread_participant_forbidden()
    {
        $thread = Thread::factory()->create();
        $call = Call::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.call.$call->id.thread.$thread->id",
        ])
            ->assertForbidden();
    }

    /** @test */
    public function non_joined_call_participant_forbidden()
    {
        $thread = $this->createGroupThread($this->doe);
        $call = Call::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->doe);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.call.$call->id.thread.$thread->id",
        ])
            ->assertForbidden();
    }

    /** @test */
    public function kicked_participant_forbidden()
    {
        $thread = $this->createGroupThread($this->doe);
        $call = Call::factory()->for($thread)->owner($this->doe)->create();
        CallParticipant::factory()->for($call)->owner($this->doe)->kicked()->create();
        $this->actingAs($this->doe);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.call.$call->id.thread.$thread->id",
        ])
            ->assertForbidden();
    }

    /** @test */
    public function inactive_call_forbidden()
    {
        $thread = $this->createGroupThread($this->tippin);
        $call = Call::factory()->for($thread)->owner($this->tippin)->ended()->create();
        CallParticipant::factory()->for($call)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.call.$call->id.thread.$thread->id",
        ])
            ->assertForbidden();
    }

    /** @test */
    public function call_participant_is_authorized()
    {
        $thread = $this->createGroupThread($this->tippin);
        $call = $this->createCall($thread, $this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.call.$call->id.thread.$thread->id",
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
