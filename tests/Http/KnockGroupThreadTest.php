<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\RateLimiter;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class KnockGroupThreadTest extends HttpTestCase
{
    /** @test */
    public function admin_can_knock_at_thread()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function non_admin_with_permission_can_knock_at_thread()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create(['send_knocks' => true]);
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function cannot_knock_when_rate_limit_exist()
    {
        $this->logCurrentRequest('GROUP');
        $thread = $this->createGroupThread($this->tippin);
        RateLimiter::hit($thread->getKnockCacheKey($this->tippin));
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $thread->id,
        ]))
            ->assertStatus(429);
    }

    /** @test */
    public function forbidden_to_knock_at_thread_when_disabled_from_settings()
    {
        $thread = Thread::factory()->group()->create(['knocks' => false]);
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_knock_at_thread_when_thread_locked()
    {
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_knock_at_thread_when_disabled_from_config()
    {
        Messenger::setKnockKnock(false);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_admin_without_permission_forbidden_to_knock_at_thread()
    {
        $this->logCurrentRequest('GROUP');
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_participant_forbidden_to_knock_at_thread()
    {
        $thread = Thread::factory()->group()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }
}
