<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallHeartbeatTest extends FeatureTestCase
{
    private Thread $group;
    private Call $call;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->createGroupThread($this->tippin, $this->doe);
        $this->call = $this->createCall($this->group, $this->tippin);
    }

    /** @test */
    public function heartbeat_cannot_be_a_post()
    {
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.heartbeat', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertStatus(405);
    }

    /** @test */
    public function heartbeat_on_missing_call_not_found()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.calls.heartbeat', [
            'thread' => $this->group->id,
            'call' => '123-456-789',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function non_call_participant_forbidden_to_use_heartbeat()
    {
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.calls.heartbeat', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function inactive_participant_forbidden_to_use_heartbeat()
    {
        $this->call->participants()
            ->first()
            ->update([
                'left_call' => now(),
            ]);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.calls.heartbeat', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_use_heartbeat_on_inactive_call()
    {
        $this->call->update([
            'call_ended' => now(),
        ]);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.calls.heartbeat', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function call_participant_can_use_heartbeat()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.calls.heartbeat', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertSuccessful();
    }
}
