<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Cache;
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

        $tippin = $this->userTippin();

        $this->group = $this->createGroupThread(
            $tippin,
            $this->userDoe()
        );

        $this->call = $this->createCall(
            $this->group,
            $tippin
        );
    }

    /** @test */
    public function heartbeat_cannot_be_a_post()
    {
        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.calls.heartbeat', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertStatus(405);
    }

    /** @test */
    public function heartbeat_on_missing_call_not_found()
    {
        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.threads.calls.heartbeat', [
            'thread' => $this->group->id,
            'call' => '123-456-789',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function non_call_participant_forbidden_to_use_heartbeat()
    {
        $this->actingAs($this->userDoe());

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

        $this->actingAs($this->userTippin());

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

        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.threads.calls.heartbeat', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function call_participant_can_use_heartbeat()
    {
        $participant = $this->call->participants()->first();

        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.threads.calls.heartbeat', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertSuccessful();

        $this->assertTrue(Cache::has("call:{$this->call->id}:{$participant->id}"));
    }
}
