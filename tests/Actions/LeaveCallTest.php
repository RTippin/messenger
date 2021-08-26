<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Calls\LeaveCall;
use RTippin\Messenger\Broadcasting\CallLeftBroadcast;
use RTippin\Messenger\Events\CallLeftEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\EndCallIfEmpty;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class LeaveCallTest extends FeatureTestCase
{
    use BroadcastLogger;

    /** @test */
    public function it_updates_participant()
    {
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->setup()->create();
        $participant = CallParticipant::factory()->for($call)->owner($this->tippin)->create();
        Carbon::setTestNow($left = now()->addMinutes(5));

        app(LeaveCall::class)->execute($call, $participant);

        $this->assertDatabaseHas('call_participants', [
            'id' => $participant->id,
            'left_call' => $left,
        ]);
    }

    /** @test */
    public function it_removes_participant_key_from_cache()
    {
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->setup()->create();
        $participant = CallParticipant::factory()->for($call)->owner($this->tippin)->create();
        Cache::put("call:$call->id:$participant->id", true);

        app(LeaveCall::class)->execute($call, $participant);

        $this->assertFalse(Cache::has("call:$call->id:$participant->id"));
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            CallLeftBroadcast::class,
            CallLeftEvent::class,
        ]);
        $thread = Thread::factory()->create();
        $call = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        $participant = CallParticipant::factory()->for($call)->owner($this->tippin)->create();

        app(LeaveCall::class)->execute($call, $participant);

        Event::assertDispatched(function (CallLeftBroadcast $event) use ($thread, $call) {
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($call->id, $event->broadcastWith()['id']);
            $this->assertSame($thread->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (CallLeftEvent $event) use ($participant) {
            return $participant->id === $event->participant->id;
        });
        $this->logBroadcast(CallLeftBroadcast::class);
    }

    /** @test */
    public function it_dispatches_subscriber_job()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->setup()->create();
        $participant = CallParticipant::factory()->for($call)->owner($this->tippin)->create();

        app(LeaveCall::class)->execute($call, $participant);

        Bus::assertDispatched(EndCallIfEmpty::class);
    }

    /** @test */
    public function it_runs_subscriber_job_now()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setCallSubscriber('queued', false);
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->setup()->create();
        $participant = CallParticipant::factory()->for($call)->owner($this->tippin)->create();

        app(LeaveCall::class)->execute($call, $participant);

        Bus::assertDispatchedSync(EndCallIfEmpty::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_job_if_disabled()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setCallSubscriber('enabled', false);
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->setup()->create();
        $participant = CallParticipant::factory()->for($call)->owner($this->tippin)->create();

        app(LeaveCall::class)->execute($call, $participant);

        Bus::assertNotDispatched(EndCallIfEmpty::class);
    }
}
