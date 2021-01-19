<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\CallLeftBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\CallLeftEvent;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class LeaveCallTest extends FeatureTestCase
{
    private Thread $group;

    private Call $call;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->group = $this->createGroupThread($this->tippin, $this->doe);

        $this->call = $this->createCall($this->group, $this->tippin);
    }

    /** @test */
    public function leaving_missing_call_not_found()
    {
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.calls.leave', [
            'thread' => $this->group->id,
            'call' => '123-456-789',
        ]))
            ->assertNotFound();
    }

    /** @test */
    public function non_call_participant_forbidden_to_leave_call()
    {
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.calls.leave', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_leave_inactive_call()
    {
        $this->call->update([
            'call_ended' => now(),
        ]);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.leave', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function call_participant_can_leave_call()
    {
        Event::fake([
            CallLeftBroadcast::class,
            CallLeftEvent::class,
        ]);

        $participant = $this->call->participants()->first();

        Cache::put("call:{$this->call->id}:{$participant->id}", true);

        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.calls.leave', [
            'thread' => $this->group->id,
            'call' => $this->call->id,
        ]))
            ->assertSuccessful();

        $this->assertNotNull($participant->fresh()->left_call);

        $this->assertFalse(Cache::has("call:{$this->call->id}:{$participant->id}"));

        Event::assertDispatched(function (CallLeftBroadcast $event) {
            $this->assertContains('private-user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($this->call->id, $event->broadcastWith()['id']);
            $this->assertSame($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (CallLeftEvent $event) use ($participant) {
            return $participant->id === $event->participant->id;
        });
    }
}
