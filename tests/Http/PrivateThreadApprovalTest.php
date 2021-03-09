<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\ThreadApprovalBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\ThreadApprovalEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PrivateThreadApprovalTest extends FeatureTestCase
{
    private Thread $private;
    private MessengerProvider $tippin;
    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->doe = $this->userDoe();
        $this->private = $this->createPrivateThread($this->tippin, $this->doe, true);
    }

    /** @test */
    public function recipient_can_approve_pending_thread()
    {
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            ThreadApprovalBroadcast::class,
            ThreadApprovalEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $this->private->id,
        ]), [
            'approve' => true,
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function recipient_can_deny_pending_thread()
    {
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            ThreadApprovalBroadcast::class,
            ThreadApprovalEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $this->private->id,
        ]), [
            'approve' => false,
        ])
            ->assertSuccessful();
    }

    /** @test */
    public function sender_cannot_deny_pending_thread()
    {
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $this->private->id,
        ]), [
            'approve' => false,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function sender_cannot_approve_pending_thread()
    {
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $this->private->id,
        ]), [
            'approve' => true,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function user_cannot_approve_non_pending_thread()
    {
        $this->makeNonPending();

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $this->private->id,
        ]), [
            'approve' => true,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function user_cannot_deny_non_pending_thread()
    {
        $this->makeNonPending();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $this->private->id,
        ]), [
            'approve' => false,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function user_cannot_approve_group_thread()
    {
        $group = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $group->id,
        ]), [
            'approve' => true,
        ])
            ->assertForbidden();
    }

    /** @test */
    public function user_cannot_deny_group_thread()
    {
        $group = $this->createGroupThread($this->tippin);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $group->id,
        ]), [
            'approve' => false,
        ])
            ->assertForbidden();
    }

    private function makeNonPending(): void
    {
        $this->private->participants()
            ->where('pending', '=', true)
            ->first()
            ->update([
                'pending' => false,
            ]);
    }
}
