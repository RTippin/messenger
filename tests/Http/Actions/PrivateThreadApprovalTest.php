<?php

namespace RTippin\Messenger\Tests\Http\Actions;

use RTippin\Messenger\Broadcasting\ThreadApprovalBroadcast;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Events\ThreadApprovalEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\UserModel;

class PrivateThreadApprovalTest extends FeatureTestCase
{
    /** @test */
    public function recipient_can_approve_pending_thread()
    {
        $this->expectsEvents([
            ThreadApprovalBroadcast::class,
            ThreadApprovalEvent::class,
        ]);

        $myself = UserModel::first();

        $thread = Thread::create(Definitions::DefaultThread);

        $otherUser = UserModel::create([
            'name' => 'Jane Smith',
            'email' => 'smith@example.net',
            'password' => 'secret',
        ]);

        $thread->participants()->create(array_merge(Definitions::DefaultParticipant, [
            'owner_id' => $myself->getKey(),
            'owner_type' => get_class($myself),
            'pending' => true,
        ]));

        $thread->participants()->create(array_merge(Definitions::DefaultParticipant, [
            'owner_id' => $otherUser->getKey(),
            'owner_type' => get_class($otherUser),
            'pending' => false,
        ]));

        $this->actingAs($myself);

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $thread->id,
        ]), [
            'approve' => true,
        ])
            ->assertSuccessful();

        $this->assertDatabaseHas('participants', [
            'owner_id' => $myself->getKey(),
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

        $myself = UserModel::first();

        $thread = Thread::create(Definitions::DefaultThread);

        $otherUser = UserModel::create([
            'name' => 'Jane Smith',
            'email' => 'smith@example.net',
            'password' => 'secret',
        ]);

        $thread->participants()->create(array_merge(Definitions::DefaultParticipant, [
            'owner_id' => $myself->getKey(),
            'owner_type' => get_class($myself),
            'pending' => true,
        ]));

        $thread->participants()->create(array_merge(Definitions::DefaultParticipant, [
            'owner_id' => $otherUser->getKey(),
            'owner_type' => get_class($otherUser),
            'pending' => false,
        ]));

        $this->actingAs($myself);

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $thread->id,
        ]), [
            'approve' => false,
        ])
            ->assertSuccessful();

        $this->assertSoftDeleted('threads', [
            'id' => $thread->id,
        ]);
    }
}
