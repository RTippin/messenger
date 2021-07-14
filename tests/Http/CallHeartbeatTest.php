<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Tests\HttpTestCase;

class CallHeartbeatTest extends HttpTestCase
{
    /** @test */
    public function heartbeat_cannot_be_a_post()
    {
        $thread = $this->createGroupThread($this->tippin);
        $call = Call::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.heartbeat', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertStatus(405);
    }

    /** @test */
    public function heartbeat_on_missing_call_not_found()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.calls.heartbeat', [
            'thread' => $thread->id,
            'call' => '123-456-789',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function non_call_participant_forbidden_to_use_heartbeat()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $call = Call::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.calls.heartbeat', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function inactive_participant_forbidden_to_use_heartbeat()
    {
        $thread = $this->createGroupThread($this->tippin);
        $call = Call::factory()->for($thread)->owner($this->tippin)->create();
        CallParticipant::factory()->for($call)->owner($this->tippin)->left()->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.calls.heartbeat', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_use_heartbeat_on_inactive_call()
    {
        $thread = $this->createGroupThread($this->tippin);
        $call = Call::factory()->for($thread)->owner($this->tippin)->ended()->create();
        CallParticipant::factory()->for($call)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.calls.heartbeat', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function call_participant_can_use_heartbeat()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $call = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        CallParticipant::factory()->for($call)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.calls.heartbeat', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertSuccessful();
    }
}
