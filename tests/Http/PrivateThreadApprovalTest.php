<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\ThreadApprovalBroadcast;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Events\ThreadApprovalEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\stubs\UserModel;

class PrivateThreadApprovalTest extends FeatureTestCase
{
    /** @test */
    public function recipient_can_approve_pending_thread()
    {
        $this->expectsEvents([
            ThreadApprovalBroadcast::class,
            ThreadApprovalEvent::class,
        ]);

        $thread = Thread::create(Definitions::DefaultThread);

        $otherUser = $this->generateJaneSmith();

        $thread->participants()->create(array_merge(Definitions::DefaultParticipant, [
            'owner_id' => 1,
            'owner_type' => self::UserModelType,
            'pending' => true,
        ]));

        $thread->participants()->create(array_merge(Definitions::DefaultParticipant, [
            'owner_id' => $otherUser->getKey(),
            'owner_type' => self::UserModelType,
            'pending' => false,
        ]));

        $this->actingAs(UserModel::find(1));

        $this->postJson(route('api.messenger.threads.approval', [
            'thread' => $thread->id,
        ]), [
            'approve' => true,
        ])
            ->assertSuccessful();

        $this->assertDatabaseHas('participants', [
            'owner_id' => 1,
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

        $thread = Thread::create(Definitions::DefaultThread);

        $otherUser = $this->generateJaneSmith();

        $thread->participants()->create(array_merge(Definitions::DefaultParticipant, [
            'owner_id' => 1,
            'owner_type' => self::UserModelType,
            'pending' => true,
        ]));

        $thread->participants()->create(array_merge(Definitions::DefaultParticipant, [
            'owner_id' => $otherUser->getKey(),
            'owner_type' => self::UserModelType,
            'pending' => false,
        ]));

        $this->actingAs(UserModel::find(1));

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
