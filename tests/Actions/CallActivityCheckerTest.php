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

        $tippin = $this->userTippin();

        $private = $this->createPrivateThread($tippin, $this->userDoe());

        $group = $this->createGroupThread($tippin);

        $this->privateCall = $this->createCall($private, $tippin);

        $this->groupCall = $this->createCall($group, $tippin);
    }

    /** @test */
    public function checker_updates_participants_left_call_when_not_in_cache()
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
    public function checker_ends_call_when_no_active_participants()
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
    public function checker_fires_no_events_when_everything_passes()
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
    public function checker_fires_left_call_events_when_removing_inactive_participants()
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
    public function checker_fires_call_ended_events_when_no_active_participants()
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
