<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class ThreadChannelTest extends HttpTestCase
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
        $thread = Thread::factory()->group()->create();

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.thread.$thread->id",
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
        $thread = Thread::factory()->group()->create();
        $this->actingAs($this->tippin);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.thread.$thread->id",
        ])
            ->assertForbidden();
    }

    /** @test */
    public function recipient_awaiting_approval_forbidden()
    {
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->pending()->create();
        $this->actingAs($this->doe);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.thread.$thread->id",
        ])
            ->assertForbidden();
    }

    /** @test */
    public function participant_awaiting_recipient_approval_authorized()
    {
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->pending()->create();
        $this->actingAs($this->tippin);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.thread.$thread->id",
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function participant_is_authorized()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.thread.$thread->id",
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

    /** @test */
    public function participant_is_forbidden_when_thread_locked()
    {
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson('/api/broadcasting/auth', [
            'channel_name' => "presence-messenger.thread.$thread->id",
        ])
            ->assertForbidden();
    }
}
