<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class ArchiveGroupThreadTest extends HttpTestCase
{
    /** @test */
    public function admin_can_check_archive_group_thread()
    {
        $this->logCurrentRequest('GROUP');
        $thread = Thread::factory()->group()->create(['subject' => 'Some Group']);
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.archive.check', [
            'thread' => $thread->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'name' => 'Some Group',
                'group' => true,
                'messages_count' => 0,
                'participants_count' => 1,
                'calls_count' => 0,
            ]);
    }

    /** @test */
    public function non_admin_forbidden_to_check_archive_group_thread()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->doe);

        $this->getJson(route('api.messenger.threads.archive.check', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_forbidden_to_check_archive_group_thread_with_active_call()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.archive.check', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_archive_group_thread()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.destroy', [
            'thread' => $thread->id,
        ]))
            ->assertStatus(204);
    }

    /** @test */
    public function non_admin_forbidden_to_archive_group_thread()
    {
        $this->logCurrentRequest();
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->actingAs($this->doe);

        $this->deleteJson(route('api.messenger.threads.destroy', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_participant_forbidden_to_archive_group_thread()
    {
        $thread = Thread::factory()->group()->create();
        $this->actingAs($this->doe);

        $this->deleteJson(route('api.messenger.threads.destroy', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_forbidden_to_archive_group_thread_with_active_call()
    {
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.destroy', [
            'thread' => $thread->id,
        ]))
            ->assertForbidden();
    }
}
