<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Calls\StoreCall;
use RTippin\Messenger\Broadcasting\CallJoinedBroadcast;
use RTippin\Messenger\Broadcasting\CallStartedBroadcast;
use RTippin\Messenger\Events\CallJoinedEvent;
use RTippin\Messenger\Events\CallStartedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\NewCallException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\SetupCall;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreCallTest extends FeatureTestCase
{
    use BroadcastLogger;

    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_stores_call_and_participant()
    {
        $thread = Thread::factory()->create();

        app(StoreCall::class)->execute($thread);

        $this->assertDatabaseHas('calls', [
            'thread_id' => $thread->id,
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'setup_complete' => false,
        ]);
        $this->assertDatabaseHas('call_participants', [
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'left_call' => null,
        ]);
    }

    /** @test */
    public function it_stores_call_setup_true()
    {
        $thread = Thread::factory()->create();

        app(StoreCall::class)->execute($thread, true);

        $this->assertDatabaseHas('calls', [
            'thread_id' => $thread->id,
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'setup_complete' => true,
        ]);
    }

    /** @test */
    public function it_throws_exception_if_cache_lock_exist()
    {
        $thread = Thread::factory()->group()->create(['subject' => 'Test']);

        Cache::lock("call:$thread->id:starting", 10)->acquire();

        $this->expectException(NewCallException::class);
        $this->expectExceptionMessage('Test has a call awaiting creation.');

        app(StoreCall::class)->execute($thread);
    }

    /** @test */
    public function it_throws_exception_if_active_call_exist_on_thread()
    {
        $thread = Thread::factory()->group()->create(['subject' => 'Test']);
        Call::factory()->for($thread)->owner($this->tippin)->setup()->create();

        $this->expectException(NewCallException::class);
        $this->expectExceptionMessage('Test already has an active call.');

        app(StoreCall::class)->execute($thread);
    }

    /** @test */
    public function it_throws_exception_if_calling_disabled()
    {
        Messenger::setCalling(false);

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Calling is currently disabled.');

        app(StoreCall::class)->execute(Thread::factory()->create());
    }

    /** @test */
    public function it_throws_exception_if_calling_temporarily_disabled()
    {
        Messenger::disableCallsTemporarily(1);

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Calling is currently disabled.');

        app(StoreCall::class)->execute(Thread::factory()->create());
    }

    /** @test */
    public function it_fires_events_to_private_thread()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            CallStartedBroadcast::class,
            CallStartedEvent::class,
            CallJoinedBroadcast::class,
            CallJoinedEvent::class,
        ]);
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        app(StoreCall::class)->execute($thread);

        Event::assertNotDispatched(CallJoinedBroadcast::class);
        Event::assertNotDispatched(CallJoinedEvent::class);
        Event::assertDispatched(function (CallStartedBroadcast $event) use ($thread) {
            $this->assertNotContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($thread->id, $event->broadcastWith()['call']['thread_id']);

            return true;
        });
        Event::assertDispatched(function (CallStartedEvent $event) use ($thread) {
            return $thread->id === $event->call->thread_id;
        });
        $this->logBroadcast(CallStartedBroadcast::class, 'Private thread.');
    }

    /** @test */
    public function it_fires_events_to_group_thread()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            CallStartedBroadcast::class,
            CallStartedEvent::class,
            CallJoinedBroadcast::class,
            CallJoinedEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin, $this->doe);

        app(StoreCall::class)->execute($thread);

        Event::assertNotDispatched(CallJoinedBroadcast::class);
        Event::assertNotDispatched(CallJoinedEvent::class);
        Event::assertDispatched(function (CallStartedBroadcast $event) use ($thread) {
            $this->assertNotContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($thread->id, $event->broadcastWith()['call']['thread_id']);

            return true;
        });
        Event::assertDispatched(function (CallStartedEvent $event) use ($thread) {
            return $thread->id === $event->call->thread_id;
        });
        $this->logBroadcast(CallStartedBroadcast::class, 'Group thread.');
    }

    /** @test */
    public function it_dispatches_subscriber_job()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();

        app(StoreCall::class)->execute(Thread::factory()->create());

        Bus::assertDispatched(SetupCall::class);
    }

    /** @test */
    public function it_runs_subscriber_job_now()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setCallSubscriber('queued', false);

        app(StoreCall::class)->execute(Thread::factory()->create());

        Bus::assertDispatchedSync(SetupCall::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_job_if_disabled()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setCallSubscriber('enabled', false);

        app(StoreCall::class)->execute(Thread::factory()->create());

        Bus::assertNotDispatched(SetupCall::class);
    }
}
