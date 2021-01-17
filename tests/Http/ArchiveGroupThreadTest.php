<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\ThreadArchivedBroadcast;
use RTippin\Messenger\Events\ThreadArchivedEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class ArchiveGroupThreadTest extends FeatureTestCase
{
    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->createGroupThread(
            $this->userTippin(),
            $this->userDoe(),
            $this->companyDevelopers()
        );
    }

    /** @test */
    public function admin_can_check_archive_group_thread()
    {
        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.threads.archive.check', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'name' => 'First Test Group',
                'group' => true,
                'messages_count' => 0,
                'participants_count' => 3,
                'calls_count' => 0,
            ]);
    }

    /** @test */
    public function non_admin_forbidden_to_check_archive_group_thread()
    {
        $this->actingAs($this->userDoe());

        $this->getJson(route('api.messenger.threads.archive.check', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_forbidden_to_check_archive_group_thread_with_active_call()
    {
        $tippin = $this->userTippin();

        $this->createCall($this->group, $tippin);

        $this->actingAs($tippin);

        $this->getJson(route('api.messenger.threads.archive.check', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_archive_group_thread()
    {
        $this->expectsEvents([
            ThreadArchivedBroadcast::class,
            ThreadArchivedEvent::class,
        ]);

        $this->actingAs($this->userTippin());

        $this->deleteJson(route('api.messenger.threads.destroy', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();

        $this->assertSoftDeleted('threads', [
            'id' => $this->group->id,
        ]);
    }

    /** @test */
    public function non_admin_forbidden_to_archive_group_thread()
    {
        $this->actingAs($this->userDoe());

        $this->deleteJson(route('api.messenger.threads.destroy', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_participant_forbidden_to_archive_group_thread()
    {
        $this->actingAs($this->createJaneSmith());

        $this->deleteJson(route('api.messenger.threads.destroy', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_forbidden_to_archive_group_thread_with_active_call()
    {
        $tippin = $this->userTippin();

        $this->createCall($this->group, $tippin);

        $this->actingAs($tippin);

        $this->deleteJson(route('api.messenger.threads.destroy', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }
}
