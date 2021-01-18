<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\ThreadApprovalBroadcast;
use RTippin\Messenger\Events\ThreadApprovalEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PrivateThreadApprovalTest extends FeatureTestCase
{
    private Thread $private;

    protected function setUp(): void
    {
        parent::setUp();

        $this->private = $this->createPrivateThread(
            $this->userTippin(),
            $this->userDoe(),
            true
        );
    }

    /** @test */
    public function recipient_can_approve_pending_thread()
    {
        $tippin = $this->userTippin();

        $this->expectsEvents([
            ThreadApprovalBroadcast::class,
            ThreadApprovalEvent::class,
        ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $this->private->id,
        ]), [
            'approve' => true,
        ])
            ->assertSuccessful();

        $this->assertDatabaseHas('participants', [
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
            'pending' => false,
        ]);
    }

    /** @test */
    public function recipient_can_deny_pending_thread()
    {
        $this->expectsEvents([
            ThreadApprovalBroadcast::class,
            ThreadApprovalEvent::class,
        ]);

        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $this->private->id,
        ]), [
            'approve' => false,
        ])
            ->assertSuccessful();

        $this->assertSoftDeleted('threads', [
            'id' => $this->private->id,
        ]);
    }

    /** @test */
    public function sender_cannot_deny_pending_thread()
    {
        $this->actingAs($this->userDoe());

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
        $this->actingAs($this->userDoe());

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

        $this->actingAs($this->userTippin());

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

        $this->actingAs($this->userTippin());

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
        $tippin = $this->userTippin();

        $group = $this->createGroupThread($tippin);

        $this->actingAs($tippin);

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
        $tippin = $this->userTippin();

        $group = $this->createGroupThread($tippin);

        $this->actingAs($tippin);

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
