<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\ThreadArchivedBroadcast;
use RTippin\Messenger\Events\ThreadArchivedEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class ArchivePrivateThreadTest extends FeatureTestCase
{
    private Thread $private;

    protected function setUp(): void
    {
        parent::setUp();

        $this->private = $this->createPrivateThread(
            $this->userTippin(),
            $this->userDoe()
        );
    }

    /** @test */
    public function user_can_check_archive_private_thread()
    {
        $this->actingAs($this->userTippin());

        $this->getJson(route('api.messenger.threads.archive.check', [
            'thread' => $this->private->id,
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
        $tippin = $this->userTippin();

        $this->createCall($this->private, $tippin);

        $this->actingAs($tippin);

        $this->getJson(route('api.messenger.threads.archive.check', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_one_can_archive_private_thread()
    {
        $this->expectsEvents([
            ThreadArchivedBroadcast::class,
            ThreadArchivedEvent::class,
        ]);

        $this->actingAs($this->userTippin());

        $this->deleteJson(route('api.messenger.threads.destroy', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();

        $this->assertSoftDeleted('threads', [
            'id' => $this->private->id,
        ]);
    }

    /** @test */
    public function user_two_can_archive_private_thread()
    {
        $doe = $this->userDoe();

        $this->expectsEvents([
            ThreadArchivedBroadcast::class,
            ThreadArchivedEvent::class,
        ]);

        $this->actingAs($doe);

        $this->deleteJson(route('api.messenger.threads.destroy', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();

        $this->assertSoftDeleted('threads', [
            'id' => $this->private->id,
        ]);
    }

    /** @test */
    public function non_participant_forbidden_to_archive_private_thread()
    {
        $this->actingAs($this->createJaneSmith());

        $this->deleteJson(route('api.messenger.threads.destroy', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_forbidden_to_archive_private_thread_with_active_call()
    {
        $tippin = $this->userTippin();

        $this->createCall($this->private, $tippin);

        $this->actingAs($tippin);

        $this->deleteJson(route('api.messenger.threads.destroy', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }
}
