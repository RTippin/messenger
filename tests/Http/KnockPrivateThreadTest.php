<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\RateLimiter;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class KnockPrivateThreadTest extends HttpTestCase
{
    /** @test */
    public function user_can_knock_at_thread()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function can_knock_when_recipient_rate_limit_exist()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        RateLimiter::hit($thread->getKnockCacheKey($this->tippin));
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function cannot_knock_when_rate_limit_exist()
    {
        $this->logCurrentRequest('PRIVATE');
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        RateLimiter::hit($thread->getKnockCacheKey($this->tippin));
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $thread->id,
        ]))
            ->assertStatus(429);
    }

    /** @test */
    public function user_forbidden_to_knock_at_thread_when_disabled_from_config()
    {
        Messenger::setKnockKnock(false);
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_forbidden_to_knock_at_thread_when_thread_locked()
    {
        $thread = Thread::factory()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_forbidden_to_knock_at_thread_when_awaiting_approval()
    {
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->pending()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function recipient_forbidden_to_knock_at_thread_when_pending_approval()
    {
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->pending()->create();
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_participant_forbidden_to_knock_at_thread()
    {
        $this->logCurrentRequest('PRIVATE');
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $this->actingAs($this->developers);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }
}
