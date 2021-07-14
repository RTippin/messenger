<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Tests\HttpTestCase;

class KickCallParticipantTest extends HttpTestCase
{
    /** @test */
    public function kick_participant_must_be_an_update()
    {
        $thread = $this->createGroupThread($this->tippin);
        $call = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        $participant = CallParticipant::factory()->for($call)->owner($this->tippin)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.participants.update', [
            'thread' => $thread->id,
            'call' => $call->id,
            'participant' => $participant->id,
        ]), [
            'kicked' => true,
        ])
            ->assertStatus(405);
    }

    /** @test */
    public function kick_participant_on_missing_participant_not_found()
    {
        $thread = $this->createGroupThread($this->tippin);
        $call = $this->createCall($thread, $this->tippin);
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.calls.participants.update', [
            'thread' => $thread->id,
            'call' => $call->id,
            'participant' => '123-456-789',
        ]), [
            'kicked' => true,
        ])
            ->assertNotFound();
    }

    /** @test */
    public function non_call_participant_forbidden_to_kick_call_participant()
    {
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $call = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        $participant = CallParticipant::factory()->for($call)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->putJson(route('api.messenger.threads.calls.participants.update', [
            'thread' => $thread->id,
            'call' => $call->id,
            'participant' => $participant->id,
        ]), [
            'kicked' => true,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function non_call_admin_participant_forbidden_to_kick_call_participant()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $call = $this->createCall($thread, $this->tippin);
        $participant = CallParticipant::factory()->for($call)->owner($this->doe)->create();
        $this->actingAs($this->doe);

        $this->putJson(route('api.messenger.threads.calls.participants.update', [
            'thread' => $thread->id,
            'call' => $call->id,
            'participant' => $participant->id,
        ]), [
            'kicked' => true,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_kick_call_participant_in_private_thread()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $call = $this->createCall($thread, $this->tippin);
        $participant = CallParticipant::factory()->for($call)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.calls.participants.update', [
            'thread' => $thread->id,
            'call' => $call->id,
            'participant' => $participant->id,
        ]), [
            'kicked' => true,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_kick_call_participant()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $call = $this->createCall($thread, $this->tippin);
        $participant = CallParticipant::factory()->for($call)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.calls.participants.update', [
            'thread' => $thread->id,
            'call' => $call->id,
            'participant' => $participant->id,
        ]), [
            'kicked' => true,
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function call_creator_can_kick_call_participant()
    {
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $call = $this->createCall($thread, $this->doe);
        $participant = CallParticipant::factory()->for($call)->owner($this->tippin)->create();
        $this->actingAs($this->doe);

        $this->putJson(route('api.messenger.threads.calls.participants.update', [
            'thread' => $thread->id,
            'call' => $call->id,
            'participant' => $participant->id,
        ]), [
            'kicked' => true,
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function admin_can_un_kick_call_participant()
    {
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $call = $this->createCall($thread, $this->tippin);
        $participant = CallParticipant::factory()->for($call)->owner($this->doe)->kicked()->create();
        $this->actingAs($this->tippin);

        $this->putJson(route('api.messenger.threads.calls.participants.update', [
            'thread' => $thread->id,
            'call' => $call->id,
            'participant' => $participant->id,
        ]), [
            'kicked' => false,
        ])
            ->assertSuccessful();
    }
}
