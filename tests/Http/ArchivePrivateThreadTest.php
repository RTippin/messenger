<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\HttpTestCase;

class ArchivePrivateThreadTest extends HttpTestCase
{
    private Thread $thread;

    protected function setUp(): void
    {
        parent::setUp();

        $this->thread = $this->createPrivateThread($this->tippin, $this->doe);
    }

    /** @test */
    public function user_can_check_archive_private_thread()
    {
        $this->logCurrentRequest('PRIVATE');
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.archive.check', [
            'thread' => $this->thread->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'name' => 'John Doe',
                'group' => false,
                'messages_count' => 0,
                'participants_count' => 2,
                'calls_count' => 0,
            ]);
    }

    /** @test */
    public function user_forbidden_to_check_archive_private_thread_with_active_call()
    {
        Call::factory()->for($this->thread)->owner($this->tippin)->setup()->create();
        $this->actingAs($this->tippin);

        $this->getJson(route('api.messenger.threads.archive.check', [
            'thread' => $this->thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_one_can_archive_private_thread()
    {
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.destroy', [
            'thread' => $this->thread->id,
        ]))
            ->assertStatus(204);
    }

    /** @test */
    public function user_two_can_archive_private_thread()
    {
        $this->actingAs($this->doe);

        $this->deleteJson(route('api.messenger.threads.destroy', [
            'thread' => $this->thread->id,
        ]))
            ->assertStatus(204);
    }

    /** @test */
    public function non_participant_forbidden_to_archive_private_thread()
    {
        $this->actingAs($this->createJaneSmith());

        $this->deleteJson(route('api.messenger.threads.destroy', [
            'thread' => $this->thread->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_forbidden_to_archive_private_thread_with_active_call()
    {
        Call::factory()->for($this->thread)->owner($this->tippin)->setup()->create();
        $this->actingAs($this->tippin);

        $this->deleteJson(route('api.messenger.threads.destroy', [
            'thread' => $this->thread->id,
        ]))
            ->assertForbidden();
    }
}
