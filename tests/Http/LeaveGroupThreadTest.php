<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Broadcasting\ThreadLeftBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\ThreadLeftEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class LeaveGroupThreadTest extends FeatureTestCase
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
    public function non_admin_can_leave()
    {
        $this->expectsEvents([
            ThreadLeftBroadcast::class,
            ThreadLeftEvent::class,
        ]);

        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function non_admin_can_leave_when_thread_locked()
    {
        $this->group->update([
            'lockout' => true,
        ]);

        $this->expectsEvents([
            ThreadLeftBroadcast::class,
            ThreadLeftEvent::class,
        ]);

        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function admin_can_leave_if_only_admin_but_thread_locked()
    {
        $this->group->update([
            'lockout' => true,
        ]);

        $this->expectsEvents([
            ThreadLeftBroadcast::class,
            ThreadLeftEvent::class,
        ]);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function admin_cannot_leave_if_only_admin_and_not_only_participant()
    {
        $this->actingAs($this->tippin);

        $this->assertSame(2, $this->group->participants()->count());

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_can_leave_when_only_participant()
    {
        $this->expectsEvents([
            ThreadLeftBroadcast::class,
            ThreadLeftEvent::class,
        ]);

        $this->group->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first()
            ->forceDelete();

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function admin_can_leave_when_other_admins_exist()
    {
        $this->expectsEvents([
            ThreadLeftBroadcast::class,
            ThreadLeftEvent::class,
        ]);

        $this->group->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first()
            ->update([
                'admin' => true,
            ]);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function cannot_leave_private_thread()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.leave', [
            'thread' => $private->id,
        ]))
            ->assertForbidden();
    }
}
