<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class KnockPrivateThreadTest extends FeatureTestCase
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
    public function user_can_knock_at_thread_when_recipient_timeout_exist()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        Cache::put("knock.knock.$thread->id.{$this->tippin->getKey()}", true);
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function user_forbidden_to_knock_at_thread_when_timeout_exist()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        Cache::put("knock.knock.$thread->id.{$this->tippin->getKey()}", true);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
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
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $this->actingAs($this->developers);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }
}
