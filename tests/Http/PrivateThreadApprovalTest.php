<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
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
        Event::fake([
            ThreadApprovalBroadcast::class,
            ThreadApprovalEvent::class,
        ]);

        $thread = Thread::create(Definitions::DefaultThread);

        $thread->participants()->create(array_merge(Definitions::DefaultParticipant, [
            'owner_id' => 1,
            'owner_type' => self::UserModelType,
            'pending' => true,
        ]));

        $thread->participants()->create(array_merge(Definitions::DefaultParticipant, [
            'owner_id' => 2,
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
            'owner_type' => self::UserModelType,
            'pending' => false,
        ]);

        Event::assertDispatched(function (ThreadApprovalBroadcast $event) use ($thread) {
            $this->assertContains('private-user.2', $event->broadcastOn());
            $this->assertEquals($thread->id, $event->broadcastWith()['thread']['id']);
            $this->assertTrue($event->broadcastWith()['thread']['approved']);

            return true;
        });

        Event::assertDispatched(function (ThreadApprovalEvent $event) use ($thread) {
            $this->assertEquals($thread->id, $event->thread->id);
            $this->assertEquals(1, $event->provider->getKey());
            $this->assertTrue($event->approved);

            return true;
        });
    }

    /** @test */
    public function recipient_can_deny_pending_thread()
    {
        Event::fake([
            ThreadApprovalBroadcast::class,
            ThreadApprovalEvent::class,
        ]);

        $thread = Thread::create(Definitions::DefaultThread);

        $thread->participants()->create(array_merge(Definitions::DefaultParticipant, [
            'owner_id' => 1,
            'owner_type' => self::UserModelType,
            'pending' => true,
        ]));

        $thread->participants()->create(array_merge(Definitions::DefaultParticipant, [
            'owner_id' => 2,
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

        Event::assertDispatched(function (ThreadApprovalBroadcast $event) use ($thread) {
            $this->assertContains('private-user.2', $event->broadcastOn());
            $this->assertEquals($thread->id, $event->broadcastWith()['thread']['id']);
            $this->assertFalse($event->broadcastWith()['thread']['approved']);

            return true;
        });

        Event::assertDispatched(function (ThreadApprovalEvent $event) use ($thread) {
            $this->assertEquals($thread->id, $event->thread->id);
            $this->assertEquals(1, $event->provider->getKey());
            $this->assertFalse($event->approved);

            return true;
        });
    }
}
