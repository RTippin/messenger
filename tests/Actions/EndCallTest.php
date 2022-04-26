<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Calls\EndCall;
use RTippin\Messenger\Broadcasting\CallEndedBroadcast;
use RTippin\Messenger\Events\CallEndedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\CallEndedMessage;
use RTippin\Messenger\Jobs\TeardownCall;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class EndCallTest extends FeatureTestCase
{
    use BroadcastLogger;

    /** @test */
    public function it_updates_call_and_active_participants()
    {
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->setup()->create();
        $participant1 = CallParticipant::factory()->for($call)->owner($this->tippin)->create();
        $participant2 = CallParticipant::factory()->for($call)->owner($this->doe)->create();
        Carbon::setTestNow($ended = now()->addMinutes(5));

        app(EndCall::class)->execute($call);

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'call_ended' => $ended,
        ]);
        $this->assertDatabaseHas('call_participants', [
            'id' => $participant1->id,
            'left_call' => $ended,
        ]);
        $this->assertDatabaseHas('call_participants', [
            'id' => $participant2->id,
            'left_call' => $ended,
        ]);
    }

    /** @test */
    public function it_does_nothing_if_call_already_ended()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            CallEndedBroadcast::class,
            CallEndedEvent::class,
        ]);
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->ended()->create();

        app(EndCall::class)->execute($call);

        Event::assertNotDispatched(CallEndedBroadcast::class);
        Event::assertNotDispatched(CallEndedEvent::class);
    }

    /** @test */
    public function it_does_nothing_if_cache_lock_exist()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            CallEndedBroadcast::class,
            CallEndedEvent::class,
        ]);
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->ended()->create();
        Cache::lock("call:$call->id:ending", 10)->acquire();

        app(EndCall::class)->execute($call);

        Event::assertNotDispatched(CallEndedBroadcast::class);
        Event::assertNotDispatched(CallEndedEvent::class);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            CallEndedBroadcast::class,
            CallEndedEvent::class,
        ]);
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $call = Call::factory()->for($thread)->owner($this->tippin)->setup()->create();

        app(EndCall::class)->execute($call);

        Event::assertDispatched(function (CallEndedBroadcast $event) use ($thread, $call) {
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($call->id, $event->broadcastWith()['id']);
            $this->assertSame($thread->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (CallEndedEvent $event) use ($call) {
            return $call->id === $event->call->id;
        });
        $this->logBroadcast(CallEndedBroadcast::class);
    }

    /** @test */
    public function it_dispatches_subscriber_jobs()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->setup()->create();

        app(EndCall::class)->withoutBroadcast()->execute($call);

        Bus::assertDispatched(TeardownCall::class);
        Bus::assertDispatched(CallEndedMessage::class);
    }

    /** @test */
    public function it_runs_subscriber_jobs_now()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('queued', false);
        Messenger::setCallSubscriber('queued', false);
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->setup()->create();

        app(EndCall::class)->withoutBroadcast()->execute($call);

        Bus::assertDispatchedSync(TeardownCall::class);
        Bus::assertDispatchedSync(CallEndedMessage::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_jobs_if_disabled()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('enabled', false);
        Messenger::setCallSubscriber('enabled', false);
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->setup()->create();

        app(EndCall::class)->withoutBroadcast()->execute($call);

        Bus::assertNotDispatched(TeardownCall::class);
        Bus::assertNotDispatched(CallEndedMessage::class);
    }
}
