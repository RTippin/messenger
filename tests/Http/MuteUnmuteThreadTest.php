<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class MuteUnmuteThreadTest extends HttpTestCase
{
    /** @test */
    public function non_participant_forbidden_to_mute_thread()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.mute', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_participant_forbidden_to_unmute_thread()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.unmute', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function participant_can_mute_thread()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.mute', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function forbidden_to_mute_thread_if_locked()
    {
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.mute', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_unmute_thread_if_locked()
    {
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.unmute', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function mute_thread_successful_if_already_muted()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->muted()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.mute', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function unmute_thread_successful_if_not_muted()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.unmute', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();
    }
}
