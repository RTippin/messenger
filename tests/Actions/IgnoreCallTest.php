<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Calls\IgnoreCall;
use RTippin\Messenger\Broadcasting\CallEndedBroadcast;
use RTippin\Messenger\Broadcasting\CallIgnoredBroadcast;
use RTippin\Messenger\Events\CallEndedEvent;
use RTippin\Messenger\Events\CallIgnoredEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class IgnoreCallTest extends FeatureTestCase
{
    use BroadcastLogger;

    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_keeps_call_active_if_ignoring_group_call()
    {
        $thread = Thread::factory()->group()->create();
        $call = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();

        app(IgnoreCall::class)->execute($thread, $call);

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
        $thread = Thread::factory()->create();
        $call = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();
        Carbon::setTestNow($ended = now());

        app(IgnoreCall::class)->execute($thread, $call);

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'call_ended' => $ended,
        ]);
    }

    /** @test */
    public function it_fires_events_if_ignoring_group_call()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            CallIgnoredBroadcast::class,
            CallIgnoredEvent::class,
            CallEndedBroadcast::class,
            CallEndedEvent::class,
        ]);
        $thread = Thread::factory()->group()->create();
        $call = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();

        app(IgnoreCall::class)->execute($thread, $call);

        Event::assertDispatched(function (CallIgnoredBroadcast $event) use ($thread, $call) {
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($call->id, $event->broadcastWith()['id']);
            $this->assertSame($thread->id, $event->broadcastWith()['thread_id']);

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
        BaseMessengerAction::enableEvents();
        Event::fake([
            CallIgnoredBroadcast::class,
            CallIgnoredEvent::class,
            CallEndedBroadcast::class,
            CallEndedEvent::class,
        ]);
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $call = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();

        app(IgnoreCall::class)->execute($thread, $call);

        Event::assertDispatched(function (CallIgnoredBroadcast $event) use ($thread, $call) {
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($call->id, $event->broadcastWith()['id']);
            $this->assertSame($thread->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (CallIgnoredEvent $event) use ($call) {
            return $call->id === $event->call->id;
        });
        Event::assertDispatched(CallEndedBroadcast::class);
        Event::assertDispatched(CallEndedEvent::class);
        $this->logBroadcast(CallIgnoredBroadcast::class);
    }
}
