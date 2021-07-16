<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Calls\CallActivityChecker;
use RTippin\Messenger\Broadcasting\CallEndedBroadcast;
use RTippin\Messenger\Broadcasting\CallLeftBroadcast;
use RTippin\Messenger\Events\CallEndedEvent;
use RTippin\Messenger\Events\CallLeftEvent;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallActivityCheckerTest extends FeatureTestCase
{
    /** @test */
    public function it_updates_participants_left_call_if_not_in_cache()
    {
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->setup()->create();
        $participant1 = CallParticipant::factory()->for($call)->owner($this->tippin)->create();
        $participant2 = CallParticipant::factory()->for($call)->owner($this->doe)->create();
        // Participant 3 will be in cache, should not be removed.
        $participant3 = CallParticipant::factory()->for($call)->owner($this->developers)->create();
        Cache::put("call:$call->id:$participant3->id", true);
        Carbon::setTestNow($left = now()->addMinutes(5));

        app(CallActivityChecker::class)->execute(Call::active()->get());

        $this->assertDatabaseHas('call_participants', [
            'id' => $participant1->id,
            'left_call' => $left,
        ]);
        $this->assertDatabaseHas('call_participants', [
            'id' => $participant2->id,
            'left_call' => $left,
        ]);
        $this->assertDatabaseHas('call_participants', [
            'id' => $participant3->id,
            'left_call' => null,
        ]);
    }

    /** @test */
    public function it_ends_calls_if_no_active_participants()
    {
        $call1 = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->setup()->create();
        CallParticipant::factory()->for($call1)->owner($this->tippin)->left()->create();
        $call2 = Call::factory()->for(Thread::factory()->create())->owner($this->doe)->setup()->create();
        Carbon::setTestNow($ended = now()->addMinutes(5));

        app(CallActivityChecker::class)->execute(Call::active()->get());

        $this->assertDatabaseHas('calls', [
            'id' => $call1->id,
            'call_ended' => $ended,
        ]);
        $this->assertDatabaseHas('calls', [
            'id' => $call2->id,
            'call_ended' => $ended,
        ]);
    }

    /** @test */
    public function it_ends_many_calls_with_no_active_participants()
    {
        $thread = Thread::factory()->create();
        Call::factory()->for($thread)->owner($this->tippin)->setup()->count(50)->create();

        app(CallActivityChecker::class)->execute(Call::active()->get());

        $this->assertSame(0, Call::active()->count());
    }

    /** @test */
    public function it_fires_no_events_if_checks_pass()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            CallLeftBroadcast::class,
            CallLeftEvent::class,
            CallEndedBroadcast::class,
            CallEndedEvent::class,
        ]);
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->setup()->create();
        $participant1 = CallParticipant::factory()->for($call)->owner($this->tippin)->create();
        $participant2 = CallParticipant::factory()->for($call)->owner($this->doe)->create();
        Cache::put("call:$call->id:$participant1->id", true);
        Cache::put("call:$call->id:$participant2->id", true);

        app(CallActivityChecker::class)->execute(Call::active()->get());

        Event::assertNotDispatched(CallLeftBroadcast::class);
        Event::assertNotDispatched(CallLeftEvent::class);
        Event::assertNotDispatched(CallEndedBroadcast::class);
        Event::assertNotDispatched(CallEndedEvent::class);
    }

    /** @test */
    public function it_fires_left_call_events_if_inactive_participants_removed()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            CallLeftBroadcast::class,
            CallLeftEvent::class,
        ]);
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->setup()->create();
        CallParticipant::factory()->for($call)->owner($this->tippin)->create();
        CallParticipant::factory()->for($call)->owner($this->doe)->create();

        app(CallActivityChecker::class)->execute(Call::active()->get());

        Event::assertDispatchedTimes(CallLeftBroadcast::class, 2);
        Event::assertDispatchedTimes(CallLeftEvent::class, 2);
    }

    /** @test */
    public function it_fires_call_ended_events_if_no_active_participants_found()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            CallEndedBroadcast::class,
            CallEndedEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);
        $call1 = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        CallParticipant::factory()->for($call1)->owner($this->tippin)->left()->create();
        Call::factory()->for($thread)->owner($this->doe)->setup()->create();

        app(CallActivityChecker::class)->execute(Call::active()->get());

        Event::assertDispatchedTimes(CallEndedBroadcast::class, 2);
        Event::assertDispatchedTimes(CallEndedEvent::class, 2);
    }
}
