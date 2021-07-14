<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class PrivateThreadApprovalTest extends HttpTestCase
{
    /** @test */
    public function recipient_can_approve_pending_thread()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->pending()->create();
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $thread->id,
        ]), [
            'approve' => true,
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function recipient_can_deny_pending_thread()
    {
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->pending()->create();
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $thread->id,
        ]), [
            'approve' => false,
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function sender_cannot_deny_pending_thread()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->pending()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $thread->id,
        ]), [
            'approve' => false,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function sender_cannot_approve_pending_thread()
    {
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->doe)->pending()->create();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $thread->id,
        ]), [
            'approve' => true,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function user_cannot_approve_non_pending_thread()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $thread->id,
        ]), [
            'approve' => true,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function user_cannot_deny_non_pending_thread()
    {
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $thread->id,
        ]), [
            'approve' => false,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function user_cannot_approve_group_thread()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $thread->id,
        ]), [
            'approve' => true,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function user_cannot_deny_group_thread()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $thread->id,
        ]), [
            'approve' => false,
        ])
            ->assertForbidden();
    }
}
