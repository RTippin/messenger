<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Tests\HttpTestCase;

class LeaveCallTest extends HttpTestCase
{
    /** @test */
    public function leaving_missing_call_not_found()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.leave', [
            'thread' => $thread->id,
            'call' => '123-456-789',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function non_call_participant_forbidden_to_leave_call()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $call = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.leave', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function left_call_participant_forbidden_to_leave_call()
    {
        $thread = $this->createGroupThread($this->tippin);
        $call = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        CallParticipant::factory()->for($call)->owner($this->tippin)->left()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.leave', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_leave_inactive_call()
    {
        $thread = $this->createGroupThread($this->tippin);
        $call = Call::factory()->for($thread)->owner($this->tippin)->ended()->create();
        CallParticipant::factory()->for($call)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.leave', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function call_participant_can_leave_call()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $call = $this->createCall($thread, $this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.leave', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertSuccessful();
    }
}
