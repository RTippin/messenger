<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Calls\EndCall;
use RTippin\Messenger\Broadcasting\CallEndedBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\CallEndedEvent;
use RTippin\Messenger\Listeners\CallEndedMessage;
use RTippin\Messenger\Listeners\TeardownCall;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class EndCallTest extends FeatureTestCase
{
    private Thread $group;

    private Call $call;

    private CallParticipant $participant;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->group = $this->createGroupThread($this->tippin, $this->doe);

        $this->call = $this->createCall($this->group, $this->tippin);

        $this->participant = $this->call->participants()->first();
    }

    /** @test */
    public function end_call_updates_call_and_active_participants()
    {
        $ended = now()->addMinutes(5);

        Carbon::setTestNow($ended);

        app(EndCall::class)->withoutDispatches()->execute($this->call);

        $this->assertDatabaseHas('calls', [
            'id' => $this->call->id,
            'call_ended' => $ended,
        ]);

        $this->assertDatabaseHas('call_participants', [
            'id' => $this->participant->id,
            'left_call' => $ended,
        ]);
    }

    /** @test */
    public function end_call_does_nothing_if_call_already_ended()
    {
        $this->call->update([
            'call_ended' => now(),
        ]);

        $this->doesntExpectEvents([
            CallEndedBroadcast::class,
            CallEndedEvent::class,
        ]);

        app(EndCall::class)->execute($this->call);
    }

    /** @test */
    public function end_call_does_nothing_if_ending_cache_key_exist()
    {
        Cache::put("call:{$this->call->id}:ending", true);

        $this->doesntExpectEvents([
            CallEndedBroadcast::class,
            CallEndedEvent::class,
        ]);

        app(EndCall::class)->execute($this->call);
    }

    /** @test */
    public function end_call_fires_events()
    {
        Event::fake([
            CallEndedBroadcast::class,
            CallEndedEvent::class,
        ]);

        app(EndCall::class)->execute($this->call);

        Event::assertDispatched(function (CallEndedBroadcast $event) {
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->call->id, $event->broadcastWith()['id']);
            $this->assertSame($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (CallEndedEvent $event) {
            return $this->call->id === $event->call->id;
        });
    }

    /** @test */
    public function end_call_triggers_listeners()
    {
        Bus::fake();

        app(EndCall::class)->withoutBroadcast()->execute($this->call);

        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === TeardownCall::class;
        });

        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === CallEndedMessage::class;
        });
    }
}
