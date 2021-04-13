<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\CallJoinedBroadcast;
use RTippin\Messenger\Events\CallJoinedEvent;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class JoinCallTest extends FeatureTestCase
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
    public function joining_missing_call_not_found()
    {
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.calls.join', [
            'thread' => $this->group->id,
            'call' => '123-456-789',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function non_participant_forbidden_to_join_call()
    {
        $this->actingAs($this->createSomeCompany());

        $this->postJson(route('api.messenger.threads.calls.join', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_join_inactive_call()
    {
        $this->call->update([
            'call_ended' => now(),
        ]);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.join', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function kicked_participant_forbidden_to_rejoin_call()
    {
        CallParticipant::factory()
            ->for($this->call)
            ->owner($this->doe)
            ->kicked()
            ->left()
            ->create();
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.calls.join', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function participant_can_join_call()
    {
        $this->actingAs($this->doe);

        $this->expectsEvents([
            CallJoinedBroadcast::class,
            CallJoinedEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.calls.join', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'call_id' => $this->call->id,
                'left_call' => null,
                'owner' => [
                    'name' => 'John Doe',
                ],
            ]);
    }

    /** @test */
    public function participant_can_rejoin_call()
    {
        $this->call->participants()
            ->first()
            ->update([
                'left_call' => now(),
            ]);
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            CallJoinedBroadcast::class,
            CallJoinedEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.calls.join', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertSuccessful();
    }
}
