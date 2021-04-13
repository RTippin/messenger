<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\CallEndedBroadcast;
use RTippin\Messenger\Events\CallEndedEvent;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class EndCallTest extends FeatureTestCase
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
    public function end_call_must_be_a_post()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.calls.end', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertStatus(405);
    }

    /** @test */
    public function end_call_on_missing_call_not_found()
    {
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $this->group->id,
            'call' => '123-456-789',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function non_call_participant_forbidden_to_end_call()
    {
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function inactive_participant_forbidden_to_end_call()
    {
        $this->call->participants()
            ->first()
            ->update([
                'left_call' => now(),
            ]);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function kicked_participant_forbidden_to_end_call()
    {
        $this->call->participants()
            ->first()
            ->update([
                'kicked' => true,
            ]);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_call_admin_participant_forbidden_to_end_call()
    {
        CallParticipant::factory()->for($this->call)->owner($this->doe)->create();
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_end_call()
    {
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            CallEndedBroadcast::class,
            CallEndedEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function call_creator_can_end_call()
    {
        $call = $this->createCall($this->group, $this->doe);
        $this->actingAs($this->doe);

        $this->expectsEvents([
            CallEndedBroadcast::class,
            CallEndedEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.calls.end', [
            'thread' => $this->group->id,
            'call' => $call->id,
        ]))
            ->assertSuccessful();
    }
}
