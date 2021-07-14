<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Tests\HttpTestCase;

class IgnoreCallTest extends HttpTestCase
{
    /** @test */
    public function ignoring_missing_call_not_found()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.ignore', [
            'thread' => $thread->id,
            'call' => '123-456-789',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function call_participant_forbidden_to_ignore_call()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $call = $this->createCall($thread, $this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.ignore', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_ignore_inactive_call()
    {
        $thread = $this->createGroupThread($this->tippin);
        $call = Call::factory()->for($thread)->owner($this->tippin)->ended()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.ignore', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_call_participant_can_ignore_call()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $call = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.ignore', [
            'thread' => $thread->id,
            'call' => $call->id,
        ]))
            ->assertSuccessful();
    }
}
