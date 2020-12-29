<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\ThreadLeftBroadcast;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Events\ThreadLeftEvent;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\stubs\UserModel;

class LeaveGroupThreadTest extends FeatureTestCase
{
    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupInitialGroup();
    }

    private function setupInitialGroup(): void
    {
        $this->group = Thread::create([
            'type' => 2,
            'subject' => 'First Test Group',
            'image' => '5.png',
            'add_participants' => true,
            'invitations' => true,
            'calling' => true,
            'knocks' => true,
        ]);

        $this->group->participants()
            ->create(array_merge(Definitions::DefaultAdminParticipant, [
                'owner_id' => 1,
                'owner_type' => self::UserModelType,
            ]));

        $this->group->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => 2,
                'owner_type' => self::UserModelType,
            ]));
    }

    /** @test */
    public function non_admin_can_leave()
    {
        Event::fake([
            ThreadLeftBroadcast::class,
            ThreadLeftEvent::class,
        ]);

        $this->actingAs(UserModel::find(2));

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();

        $this->assertSoftDeleted('participants', [
            'thread_id' => $this->group->id,
            'owner_id' => 2,
            'owner_type' => self::UserModelType,
        ]);

        Event::assertDispatched(function (ThreadLeftBroadcast $event) {
            $this->assertContains('private-user.2', $event->broadcastOn());
            $this->assertArrayHasKey('thread_id', $event->broadcastWith());
            $this->assertEquals($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (ThreadLeftEvent $event) {
            $this->assertEquals(2, $event->provider->getKey());
            $this->assertEquals($this->group->id, $event->thread->id);
            $this->assertEquals(2, $event->participant->owner_id);

            return true;
        });
    }

    /** @test */
    public function admin_cannot_leave_if_only_admin_and_not_only_participant()
    {
        $this->actingAs(UserModel::find(1));

        $this->assertEquals(2, $this->group->participants()->count());

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_leave_when_only_participant()
    {
        Event::fake([
            ThreadLeftBroadcast::class,
            ThreadLeftEvent::class,
        ]);

        Participant::where('thread_id', '=', $this->group->id)
            ->where('owner_id', '=', 2)
            ->where('owner_type', '=', self::UserModelType)
            ->first()
            ->forceDelete();

        $this->actingAs(UserModel::find(1));

        $this->assertEquals(1, $this->group->participants()->count());

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();

        $this->assertSoftDeleted('participants', [
            'thread_id' => $this->group->id,
            'owner_id' => 1,
            'owner_type' => self::UserModelType,
        ]);

        Event::assertDispatched(function (ThreadLeftBroadcast $event) {
            $this->assertContains('private-user.1', $event->broadcastOn());
            $this->assertArrayHasKey('thread_id', $event->broadcastWith());
            $this->assertEquals($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (ThreadLeftEvent $event) {
            $this->assertEquals(1, $event->provider->getKey());
            $this->assertEquals($this->group->id, $event->thread->id);
            $this->assertEquals(1, $event->participant->owner_id);

            return true;
        });
    }

    /** @test */
    public function admin_can_leave_when_other_admins_exist()
    {
        Event::fake([
            ThreadLeftBroadcast::class,
            ThreadLeftEvent::class,
        ]);

        Participant::where('thread_id', '=', $this->group->id)
            ->where('owner_id', '=', 2)
            ->where('owner_type', '=', self::UserModelType)
            ->first()
            ->update([
                'admin' => true,
            ]);

        $this->actingAs(UserModel::find(1));

        $this->assertEquals(2, $this->group->participants()->admins()->count());

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();

        $this->assertSoftDeleted('participants', [
            'thread_id' => $this->group->id,
            'owner_id' => 1,
            'owner_type' => self::UserModelType,
        ]);

        Event::assertDispatched(function (ThreadLeftBroadcast $event) {
            $this->assertContains('private-user.1', $event->broadcastOn());
            $this->assertArrayHasKey('thread_id', $event->broadcastWith());
            $this->assertEquals($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (ThreadLeftEvent $event) {
            $this->assertEquals(1, $event->provider->getKey());
            $this->assertEquals($this->group->id, $event->thread->id);
            $this->assertEquals(1, $event->participant->owner_id);

            return true;
        });
    }

    /** @test */
    public function cannot_leave_private_thread()
    {
        $private = Thread::create(Definitions::DefaultThread);

        $private->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => 1,
                'owner_type' => self::UserModelType,
            ]));

        $private->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => 2,
                'owner_type' => self::UserModelType,
            ]));

        $this->actingAs(UserModel::find(1));

        $this->getJson(route('api.messenger.threads.show', [
            'thread' => $private->id,
        ]))
            ->assertSuccessful();

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $private->id,
        ]))
            ->assertForbidden();
    }
}
