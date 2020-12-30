<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
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

        $this->private = $this->makePrivateThread(
            $this->userTippin(),
            $this->userDoe()
        );
    }

    /** @test */
    public function user_one_can_archive_private_thread()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        Event::fake([
            ThreadArchivedBroadcast::class,
            ThreadArchivedEvent::class,
        ]);

        $this->actingAs($tippin);

        $this->deleteJson(route('api.messenger.threads.destroy', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();

        $this->assertSoftDeleted('threads', [
            'id' => $this->private->id,
        ]);

        Event::assertDispatched(function (ThreadArchivedBroadcast $event) use ($tippin, $doe) {
            $this->assertContains('private-user.'.$tippin->getKey(), $event->broadcastOn());
            $this->assertContains('private-user.'.$doe->getKey(), $event->broadcastOn());
            $this->assertEquals($this->private->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (ThreadArchivedEvent $event) use ($tippin) {
            $this->assertEquals($tippin->getKey(), $event->provider->getKey());
            $this->assertEquals($this->private->id, $event->thread->id);

            return true;
        });
    }

    /** @test */
    public function user_two_can_archive_private_thread()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        Event::fake([
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

        Event::assertDispatched(function (ThreadArchivedBroadcast $event) use ($tippin, $doe) {
            $this->assertContains('private-user.'.$tippin->getKey(), $event->broadcastOn());
            $this->assertContains('private-user.'.$doe->getKey(), $event->broadcastOn());
            $this->assertEquals($this->private->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (ThreadArchivedEvent $event) use ($doe) {
            $this->assertEquals($doe->getKey(), $event->provider->getKey());
            $this->assertEquals($this->private->id, $event->thread->id);

            return true;
        });
    }

    /** @test */
    public function non_participant_forbidden_to_archive_valid_private_thread()
    {
        $this->actingAs($this->generateJaneSmith());

        $this->deleteJson(route('api.messenger.threads.destroy', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_archive_valid_private_thread_with_active_call()
    {
        $tippin = $this->userTippin();

        $this->makeActiveCallOnThread($this->private, $tippin);

        $this->actingAs($tippin);

        $this->deleteJson(route('api.messenger.threads.destroy', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }
}
