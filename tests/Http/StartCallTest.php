<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class StartCallTest extends HttpTestCase
{
    /** @test */
    public function non_participant_forbidden_to_start_call()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function participant_without_permission_forbidden_to_start_call()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_start_call_on_private_awaiting_recipient_approval()
    {
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->pending()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_start_call_on_private_awaiting_our_approval()
    {
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->pending()->create();
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_start_call_when_active_call_exist()
    {
        $thread = $this->createGroupThread($this->tippin);
        Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_start_call_when_creating_call_timeout_exist()
    {
        $thread = $this->createGroupThread($this->tippin);
        Cache::lock("call:$thread->id:starting", 10)->acquire();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_start_call_when_disabled_from_group_settings()
    {
        $thread = Thread::factory()->group()->create(['calling' => false]);
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_start_call_when_disabled_from_config()
    {
        Messenger::setCalling(false);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_start_call_when_temporarily_disabled()
    {
        Messenger::disableCallsTemporarily(1);
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_can_start_call_in_private()
    {
        $this->logCurrentRequest('PRIVATE');
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function admin_can_start_call_in_group()
    {
        $this->logCurrentRequest('GROUP');
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function participant_with_permission_can_start_call_in_group()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create(['start_calls' => true]);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();
    }
}
