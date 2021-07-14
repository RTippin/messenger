<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Tests\HttpTestCase;

class JoinCallTest extends HttpTestCase
{
    /** @test */
    public function joining_missing_call_not_found()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.join', [
            'thread' => $thread->id,
            'call' => '123-456-789',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function non_participant_forbidden_to_join_call()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $call = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.calls.join', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_join_inactive_call()
    {
        $thread = $this->createGroupThread($this->tippin);
        $call = Call::factory()->for($thread)->owner($this->tippin)->ended()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.join', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function kicked_participant_forbidden_to_rejoin_call()
    {
        $thread = $this->createGroupThread($this->tippin);
        $call = Call::factory()->for($thread)->owner($this->tippin)->create();
        CallParticipant::factory()->for($call)->owner($this->tippin)->kicked()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.join', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function participant_can_join_call()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $call = Call::factory()->for($thread)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.join', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'call_id' => $call->id,
            ]);
    }

    /** @test */
    public function participant_can_rejoin_call()
    {
        $thread = $this->createGroupThread($this->tippin);
        $call = Call::factory()->for($thread)->owner($this->tippin)->create();
        CallParticipant::factory()->for($call)->owner($this->tippin)->left()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.join', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertSuccessful();
    }
}
