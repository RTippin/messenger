<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class EndCallTest extends HttpTestCase
{
    /** @test */
    public function end_call_must_be_a_post()
    {
        $thread = Thread::factory()->create();
        $call = Call::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.calls.end', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertStatus(405);
    }

    /** @test */
    public function end_call_on_missing_call_not_found()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $thread->id,
            'call' => '123-456-789',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function non_call_participant_forbidden_to_end_call()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $call = $this->createCall($thread, $this->tippin);
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function inactive_participant_forbidden_to_end_call()
    {
        $thread = $this->createGroupThread($this->tippin);
        $call = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        CallParticipant::factory()->for($call)->owner($this->tippin)->left()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function kicked_participant_forbidden_to_end_call()
    {
        $thread = $this->createGroupThread($this->tippin);
        $call = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        CallParticipant::factory()->for($call)->owner($this->tippin)->kicked()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_call_admin_participant_forbidden_to_end_call()
    {
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $call = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        CallParticipant::factory()->for($call)->owner($this->doe)->create();
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_end_call()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $call = $this->createCall($thread, $this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function call_creator_can_end_call()
    {
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $call = $this->createCall($thread, $this->doe);
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function recipient_can_end_call_in_private_thread()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $call = $this->createCall($thread, $this->tippin, $this->doe);
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertSuccessful();
    }
}
