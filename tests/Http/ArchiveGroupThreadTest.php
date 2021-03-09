<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\ThreadArchivedBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\ThreadArchivedEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class ArchiveGroupThreadTest extends FeatureTestCase
{
    private Thread $group;
    private MessengerProvider $tippin;
    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->doe = $this->userDoe();
        $this->group = $this->createGroupThread($this->tippin, $this->doe);
    }

    /** @test */
    public function admin_can_check_archive_group_thread()
    {
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.archive.check', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'name' => 'First Test Group',
                'group' => true,
                'messages_count' => 0,
                'participants_count' => 2,
                'calls_count' => 0,
            ]);
    }

    /** @test */
    public function non_admin_forbidden_to_check_archive_group_thread()
    {
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.archive.check', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_forbidden_to_check_archive_group_thread_with_active_call()
    {
        $this->createCall($this->group, $this->tippin);
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.archive.check', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_archive_group_thread()
    {
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            ThreadArchivedBroadcast::class,
            ThreadArchivedEvent::class,
        ]);

        $this->deleteJson(route('api.messenger.threads.destroy', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function non_admin_forbidden_to_archive_group_thread()
    {
        $this->actingAs($this->doe);

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
        $this->createCall($this->group, $this->tippin);
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.destroy', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }
}
