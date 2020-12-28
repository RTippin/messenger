<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\ThreadLeftBroadcast;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Events\ThreadLeftEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\UserModel;

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
    public function non_admin_can_leave_group_thread()
    {
        $this->expectsEvents([
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
        ]);
    }
}
