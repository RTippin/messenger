<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Calls\IgnoreCall;
use RTippin\Messenger\Broadcasting\CallEndedBroadcast;
use RTippin\Messenger\Broadcasting\CallIgnoredBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\CallEndedEvent;
use RTippin\Messenger\Events\CallIgnoredEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class IgnoreCallTest extends FeatureTestCase
{
    private MessengerProvider $tippin;
    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->doe = $this->userDoe();
    }

    /** @test */
    public function it_keeps_call_active_if_ignoring_group_call()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $call = $this->createCall($group, $this->tippin);
        Messenger::setProvider($this->doe);

        app(IgnoreCall::class)->withoutDispatches()->execute($group, $call);

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'setup_complete' => true,
            'teardown_complete' => false,
            'call_ended' => null,
        ]);
    }

    /** @test */
    public function it_ends_call_if_ignoring_private_call()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);
        $call = $this->createCall($private, $this->tippin);
        $ended = now();
        Carbon::setTestNow($ended);
        Messenger::setProvider($this->doe);

        app(IgnoreCall::class)->withoutDispatches()->execute($private, $call);

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'call_ended' => $ended,
        ]);
    }

    /** @test */
    public function it_fires_events_if_ignoring_group_call()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe);
        $call = $this->createCall($group, $this->tippin);
        Messenger::setProvider($this->doe);
        Event::fake([
            CallIgnoredBroadcast::class,
            CallIgnoredEvent::class,
            CallEndedBroadcast::class,
            CallEndedEvent::class,
        ]);

        app(IgnoreCall::class)->execute($group, $call);

        Event::assertDispatched(function (CallIgnoredBroadcast $event) use ($group, $call) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($call->id, $event->broadcastWith()['id']);
            $this->assertSame($group->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (CallIgnoredEvent $event) use ($call) {
            return $call->id === $event->call->id;
        });
        Event::assertNotDispatched(CallEndedBroadcast::class);
        Event::assertNotDispatched(CallEndedEvent::class);
    }

    /** @test */
    public function it_fires_events_if_ignoring_private_call()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);
        $call = $this->createCall($private, $this->tippin);
        Messenger::setProvider($this->doe);
        Event::fake([
            CallIgnoredBroadcast::class,
            CallIgnoredEvent::class,
            CallEndedBroadcast::class,
            CallEndedEvent::class,
        ]);

        app(IgnoreCall::class)->execute($private, $call);

        Event::assertDispatched(function (CallIgnoredBroadcast $event) use ($private, $call) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($call->id, $event->broadcastWith()['id']);
            $this->assertSame($private->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (CallIgnoredEvent $event) use ($call) {
            return $call->id === $event->call->id;
        });
        Event::assertDispatched(CallEndedBroadcast::class);
        Event::assertDispatched(CallEndedEvent::class);
    }
}
