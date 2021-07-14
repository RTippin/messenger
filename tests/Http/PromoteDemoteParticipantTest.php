<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class PromoteDemoteParticipantTest extends HttpTestCase
{
    /** @test */
    public function forbidden_to_promote_admin_role_in_private_thread()
    {
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.participants.promote', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_forbidden_to_demote_admin_role_in_private_thread()
    {
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.participants.demote', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_admin_forbidden_to_demote_admin()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.participants.demote', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_admin_forbidden_to_promote_admin()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.participants.promote', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_forbidden_to_promote_existing_admin()
    {
        $thread = $this->createGroupThread($this->tippin);
        $participant = Participant::factory()->for($thread)->owner($this->doe)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.participants.promote', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_forbidden_to_demote_non_admin()
    {
        $thread = $this->createGroupThread($this->tippin);
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.participants.demote', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_promote_participant_to_admin()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.participants.promote', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $participant->id,
                'admin' => true,
            ]);
    }

    /** @test */
    public function admin_forbidden_to_promote_participant_when_thread_locked()
    {
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.participants.promote', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_demote_admin()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $participant = Participant::factory()->for($thread)->owner($this->doe)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.participants.demote', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'id' => $participant->id,
                'admin' => false,
            ]);
    }

    /** @test */
    public function admin_forbidden_to_demote_admin_when_thread_locked()
    {
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.participants.demote', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }
}
