<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\ThreadLeftBroadcast;
use RTippin\Messenger\Events\ThreadLeftEvent;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class LeaveGroupThreadTest extends FeatureTestCase
{
    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->makeGroupThread(
            $this->userTippin(),
            $this->userDoe()
        );
    }

    /** @test */
    public function non_admin_can_leave()
    {
        $doe = $this->userDoe();

        Event::fake([
            ThreadLeftBroadcast::class,
            ThreadLeftEvent::class,
        ]);

        $this->actingAs($doe);

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();

        $this->assertSoftDeleted('participants', [
            'thread_id' => $this->group->id,
            'owner_id' => $doe->getKey(),
            'owner_type' => get_class($doe),
        ]);

        Event::assertDispatched(function (ThreadLeftBroadcast $event) use ($doe) {
            $this->assertContains('private-user.'.$doe->getKey(), $event->broadcastOn());
            $this->assertEquals($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (ThreadLeftEvent $event) use ($doe) {
            $this->assertEquals($doe->getKey(), $event->provider->getKey());
            $this->assertEquals($this->group->id, $event->thread->id);
            $this->assertEquals($doe->getKey(), $event->participant->owner_id);

            return true;
        });
    }

    /** @test */
    public function admin_cannot_leave_if_only_admin_and_not_only_participant()
    {
        $this->actingAs($this->userTippin());

        $this->assertEquals(2, $this->group->participants()->count());

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_leave_when_only_participant()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        Event::fake([
            ThreadLeftBroadcast::class,
            ThreadLeftEvent::class,
        ]);

        Participant::where('thread_id', '=', $this->group->id)
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first()
            ->forceDelete();

        $this->actingAs($tippin);

        $this->assertEquals(1, $this->group->participants()->count());

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();

        $this->assertSoftDeleted('participants', [
            'thread_id' => $this->group->id,
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
        ]);

        Event::assertDispatched(function (ThreadLeftBroadcast $event) use ($tippin) {
            $this->assertContains('private-user.'.$tippin->getKey(), $event->broadcastOn());
            $this->assertEquals($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (ThreadLeftEvent $event) use ($tippin) {
            $this->assertEquals($tippin->getKey(), $event->provider->getKey());
            $this->assertEquals($this->group->id, $event->thread->id);
            $this->assertEquals($tippin->getKey(), $event->participant->owner_id);

            return true;
        });
    }

    /** @test */
    public function admin_can_leave_when_other_admins_exist()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        Event::fake([
            ThreadLeftBroadcast::class,
            ThreadLeftEvent::class,
        ]);

        Participant::where('thread_id', '=', $this->group->id)
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first()
            ->update([
                'admin' => true,
            ]);

        $this->actingAs($tippin);

        $this->assertEquals(2, $this->group->participants()->admins()->count());

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();

        $this->assertSoftDeleted('participants', [
            'thread_id' => $this->group->id,
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
        ]);

        Event::assertDispatched(function (ThreadLeftBroadcast $event) use ($tippin) {
            $this->assertContains('private-user.'.$tippin->getKey(), $event->broadcastOn());
            $this->assertEquals($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (ThreadLeftEvent $event) use ($tippin) {
            $this->assertEquals($tippin->getKey(), $event->provider->getKey());
            $this->assertEquals($this->group->id, $event->thread->id);
            $this->assertEquals($tippin->getKey(), $event->participant->owner_id);

            return true;
        });
    }

    /** @test */
    public function cannot_leave_private_thread()
    {
        $tippin = $this->userTippin();

        $private = $this->makePrivateThread(
            $tippin,
            $this->userDoe()
        );

        $this->actingAs($tippin);

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
