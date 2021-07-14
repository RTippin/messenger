<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class LeaveGroupThreadTest extends HttpTestCase
{
    /** @test */
    public function non_admin_can_leave()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function non_admin_can_leave_when_thread_locked()
    {
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function admin_can_leave_if_only_admin_but_thread_locked()
    {
        $thread = Thread::factory()->group()->locked()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function admin_cannot_leave_if_only_admin_and_not_only_participant()
    {
        $this->logCurrentRequest('ONLY_ADMIN');
        $thread = $this->createGroupThread($this->tippin, $this->doe);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_leave_when_only_participant()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function admin_can_leave_when_other_admins_exist()
    {
        $thread = $this->createGroupThread($this->tippin);
        Participant::factory()->for($thread)->owner($this->doe)->admin()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function non_participant_forbidden_to_leave()
    {
        $this->logCurrentRequest();
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function cannot_leave_private_thread()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $private->id,
        ]))
            ->assertForbidden();
    }
}
