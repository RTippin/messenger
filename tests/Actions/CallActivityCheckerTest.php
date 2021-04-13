<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Calls\CallActivityChecker;
use RTippin\Messenger\Broadcasting\CallEndedBroadcast;
use RTippin\Messenger\Broadcasting\CallLeftBroadcast;
use RTippin\Messenger\Events\CallEndedEvent;
use RTippin\Messenger\Events\CallLeftEvent;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallActivityCheckerTest extends FeatureTestCase
{
    private Call $privateCall;
    private Call $groupCall;

    protected function setUp(): void
    {
        parent::setUp();

        $private = $this->createPrivateThread($this->tippin, $this->doe);
        $group = $this->createGroupThread($this->tippin);
        $this->privateCall = $this->createCall($private, $this->tippin);
        $this->groupCall = $this->createCall($group, $this->tippin);
    }

    /** @test */
    public function it_updates_participants_left_call_if_not_in_cache()
    {
        $left = now()->addMinutes(5);
        Carbon::setTestNow($left);

        app(CallActivityChecker::class)->execute(Call::active()->get());

        $this->assertDatabaseHas('call_participants', [
            'call_id' => $this->privateCall->id,
            'left_call' => $left,
        ]);
        $this->assertDatabaseHas('call_participants', [
            'call_id' => $this->groupCall->id,
            'left_call' => $left,
        ]);
    }

    /** @test */
    public function it_ends_call_if_no_active_participants()
    {
        $ended = now()->addMinutes(5);
        Carbon::setTestNow($ended);
        DB::table('call_participants')->update([
            'left_call' => now(),
        ]);

        app(CallActivityChecker::class)->execute(Call::active()->get());

        $this->assertDatabaseHas('calls', [
            'id' => $this->privateCall->id,
            'call_ended' => $ended,
        ]);
        $this->assertDatabaseHas('calls', [
            'id' => $this->groupCall->id,
            'call_ended' => $ended,
        ]);
    }

    /** @test */
    public function it_fires_no_events_if_checks_pass()
    {
        $privateParticipant = $this->privateCall->participants()->first();
        $groupParticipant = $this->groupCall->participants()->first();
        Cache::put("call:{$this->privateCall->id}:{$privateParticipant->id}", true);
        Cache::put("call:{$this->groupCall->id}:{$groupParticipant->id}", true);

        $this->doesntExpectEvents([
            CallLeftBroadcast::class,
            CallLeftEvent::class,
            CallEndedBroadcast::class,
            CallEndedEvent::class,
        ]);

        app(CallActivityChecker::class)->execute(Call::active()->get());
    }

    /** @test */
    public function it_fires_left_call_events_if_inactive_participants_removed()
    {
        Event::fake([
            CallLeftBroadcast::class,
            CallLeftEvent::class,
        ]);

        app(CallActivityChecker::class)->execute(Call::active()->get());

        Event::assertDispatchedTimes(CallLeftBroadcast::class, 2);
        Event::assertDispatchedTimes(CallLeftEvent::class, 2);
    }

    /** @test */
    public function it_fires_call_ended_events_if_no_active_participants_found()
    {
        DB::table('call_participants')->update([
            'left_call' => now(),
        ]);
        Event::fake([
            CallEndedBroadcast::class,
            CallEndedEvent::class,
        ]);

        app(CallActivityChecker::class)->execute(Call::active()->get());

        Event::assertDispatchedTimes(CallEndedBroadcast::class, 2);
        Event::assertDispatchedTimes(CallEndedEvent::class, 2);
    }
}
