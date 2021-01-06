<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\CallJoinedBroadcast;
use RTippin\Messenger\Broadcasting\CallStartedBroadcast;
use RTippin\Messenger\Events\CallJoinedEvent;
use RTippin\Messenger\Events\CallStartedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class StartCallTest extends FeatureTestCase
{
    private Thread $private;

    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $this->private = $this->createPrivateThread(
            $tippin,
            $doe
        );

        $this->group = $this->createGroupThread(
            $tippin,
            $doe,
            $this->companyDevelopers()
        );
    }

    /** @test */
    public function non_participant_forbidden_to_start_call_in_private()
    {
        $this->actingAs($this->companyDevelopers());

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_participant_forbidden_to_start_call_in_group()
    {
        $this->actingAs($this->companyLaravel());

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function participant_without_permission_forbidden_to_start_call()
    {
        $this->actingAs($this->userDoe());

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_start_call_on_pending_private()
    {
        $doe = $this->userDoe();

        $this->private->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first()
            ->update([
                'pending' => true,
            ]);

        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_start_call_when_active_call_exist()
    {
        $tippin = $this->userTippin();

        $this->createCall($this->private, $tippin);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_start_call_when_creating_call_timeout_exist()
    {
        Cache::put("call:{$this->private->id}:starting", true);

        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_start_call_when_disabled_from_group_settings()
    {
        $this->group->update([
            'calling' => false,
        ]);

        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_start_call_when_disabled_from_config()
    {
        Messenger::setCalling(false);

        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_can_start_call_in_private()
    {
        $doe = $this->userDoe();

        $tippin = $this->userTippin();

        Event::fake([
            CallStartedBroadcast::class,
            CallStartedEvent::class,
            CallJoinedBroadcast::class,
            CallJoinedEvent::class,
        ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $this->private->id,
                'options' => [
                    'setup_complete' => false,
                ],
            ]);

        $this->assertDatabaseHas('call_participants', [
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
        ]);

        $this->assertDatabaseHas('calls', [
            'thread_id' => $this->private->id,
        ]);

        Event::assertNotDispatched(CallJoinedBroadcast::class);

        Event::assertNotDispatched(CallJoinedEvent::class);

        Event::assertDispatched(function (CallStartedBroadcast $event) use ($tippin, $doe) {
            $this->assertNotContains('private-user.'.$tippin->getKey(), $event->broadcastOn());
            $this->assertContains('private-user.'.$doe->getKey(), $event->broadcastOn());
            $this->assertEquals($this->private->id, $event->broadcastWith()['call']['thread_id']);

            return true;
        });

        Event::assertDispatched(function (CallStartedEvent $event) {
            return $this->private->id === $event->call->thread_id;
        });
    }

    /** @test */
    public function admin_can_start_call_in_group()
    {
        $tippin = $this->userTippin();

        $this->expectsEvents([
            CallStartedBroadcast::class,
            CallStartedEvent::class,
        ]);

        $this->doesntExpectEvents([
            CallJoinedBroadcast::class,
            CallJoinedEvent::class,
        ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();

        $this->assertDatabaseHas('call_participants', [
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
        ]);

        $this->assertDatabaseHas('calls', [
            'thread_id' => $this->group->id,
        ]);
    }

    /** @test */
    public function participant_with_permission_can_start_call_in_group()
    {
        $doe = $this->userDoe();

        $this->group->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first()
            ->update([
                'start_calls' => true,
            ]);

        $this->expectsEvents([
            CallStartedBroadcast::class,
            CallStartedEvent::class,
        ]);

        $this->doesntExpectEvents([
            CallJoinedBroadcast::class,
            CallJoinedEvent::class,
        ]);

        $this->actingAs($doe);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();

        $this->assertDatabaseHas('call_participants', [
            'owner_id' => $doe->getKey(),
            'owner_type' => get_class($doe),
        ]);

        $this->assertDatabaseHas('calls', [
            'thread_id' => $this->group->id,
        ]);
    }
}
