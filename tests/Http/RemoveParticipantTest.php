<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class RemoveParticipantTest extends HttpTestCase
{
    /** @test */
    public function user_forbidden_to_remove_participant_from_private_thread()
    {
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.participants.destroy', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_admin_forbidden_to_remove_participant()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->doe);

        $this->deleteJson(route('api.messenger.threads.participants.destroy', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_remove_participant()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.participants.destroy', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]))
            ->assertStatus(204);
    }

    /** @test */
    public function admin_can_remove_another_admin()
    {
        $thread = $this->createGroupThread($this->tippin);
        $participant = Participant::factory()->for($thread)->owner($this->doe)->admin()->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.participants.destroy', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]))
            ->assertStatus(204);
    }

    /** @test */
    public function admin_forbidden_to_remove_participant_when_thread_locked()
    {
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.participants.destroy', [
            'thread' => $thread->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }
}
