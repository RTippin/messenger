<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Calls\LeaveCall;
use RTippin\Messenger\Broadcasting\CallLeftBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\CallLeftEvent;
use RTippin\Messenger\Listeners\EndCallIfEmpty;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class LeaveCallTest extends FeatureTestCase
{
    private Thread $group;
    private Call $call;
    private CallParticipant $participant;
    private MessengerProvider $tippin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->group = $this->createGroupThread($this->tippin);
        $this->call = $this->createCall($this->group, $this->tippin);
        $this->participant = $this->call->participants()->first();
    }

    /** @test */
    public function it_updates_participant()
    {
        $left = now()->addMinutes(5);
        Carbon::setTestNow($left);

        app(LeaveCall::class)->withoutDispatches()->execute(
            $this->call,
            $this->participant
        );

        $this->assertDatabaseHas('call_participants', [
            'id' => $this->participant->id,
            'left_call' => $left,
        ]);
    }

    /** @test */
    public function it_removes_participant_key_from_cache()
    {
        Cache::put("call:{$this->call->id}:{$this->participant->id}", true);

        app(LeaveCall::class)->withoutDispatches()->execute(
            $this->call,
            $this->participant
        );

        $this->assertFalse(Cache::has("call:{$this->call->id}:{$this->participant->id}"));
    }

    /** @test */
    public function it_fires_events()
    {
        Event::fake([
            CallLeftBroadcast::class,
            CallLeftEvent::class,
        ]);

        app(LeaveCall::class)->execute(
            $this->call,
            $this->participant
        );

        Event::assertDispatched(function (CallLeftBroadcast $event) {
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($this->call->id, $event->broadcastWith()['id']);
            $this->assertSame($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (CallLeftEvent $event) {
            return $this->participant->id === $event->participant->id;
        });
    }

    /** @test */
    public function it_dispatches_listeners()
    {
        Bus::fake();

        app(LeaveCall::class)->withoutBroadcast()->execute(
            $this->call,
            $this->participant
        );

        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === EndCallIfEmpty::class;
        });
    }
}
