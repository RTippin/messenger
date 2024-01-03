<?php

namespace RTippin\Messenger\Tests\Brokers;

use Exception;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\MessengerBroadcast;
use RTippin\Messenger\Brokers\BroadcastBroker;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\BroadcastFailedEvent;
use RTippin\Messenger\Events\PushNotificationEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\CompanyModel;
use RTippin\Messenger\Tests\Fixtures\OtherModel;
use RTippin\Messenger\Tests\Fixtures\UserModel;

class BroadcastBrokerTest extends FeatureTestCase
{
    const WITH = [
        'data' => 1234,
    ];

    /** @test */
    public function container_uses_default_broadcast_broker()
    {
        $this->assertInstanceOf(BroadcastBroker::class, app(BroadcastDriver::class));
    }

    /** @test */
    public function it_can_be_resolved_via_the_helper()
    {
        $this->assertInstanceOf(BroadcastBroker::class, broadcaster());
    }

    /** @test */
    public function it_ignores_invalid_event()
    {
        Event::fake([
            InvalidBroadcastEvent::class,
        ]);

        app(BroadcastBroker::class)
            ->to($this->tippin)
            ->with(self::WITH)
            ->broadcast(InvalidBroadcastEvent::class);

        Event::assertNotDispatched(InvalidBroadcastEvent::class);
    }

    /** @test */
    public function it_fires_failed_broadcast_event_when_exception_thrown()
    {
        Event::fake([
            BroadcastFailedEvent::class,
            FakeBroadcastEvent::class,
        ]);
        Broadcast::shouldReceive('event')->andThrow(new Exception('Pusher Failed.'));

        app(BroadcastBroker::class)
            ->to($this->tippin)
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertNotDispatched(FakeBroadcastEvent::class);
        Event::assertDispatched(function (BroadcastFailedEvent $event) {
            $this->assertSame(FakeBroadcastEvent::class, $event->abstractBroadcast);
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->channels);
            $this->assertSame(self::WITH, $event->with);
            $this->assertSame('Pusher Failed.', $event->exception->getMessage());

            return true;
        });
    }

    /** @test */
    public function it_doesnt_fire_events_if_no_recipients()
    {
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        app(BroadcastBroker::class)
            ->to(null)
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertNotDispatched(FakeBroadcastEvent::class);
    }

    /** @test */
    public function it_dispatches_push_notification_if_enabled()
    {
        Messenger::setPushNotifications(true);
        Event::fake([
            FakeBroadcastEvent::class,
            PushNotificationEvent::class,
        ]);

        app(BroadcastBroker::class)
            ->to($this->tippin)
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertDispatched(FakeBroadcastEvent::class);
        Event::assertDispatched(PushNotificationEvent::class);
    }

    /** @test */
    public function it_ignores_invalid_providers()
    {
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        app(BroadcastBroker::class)
            ->toSelected(collect([
                $this->tippin,
                new OtherModel,
            ]))
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        $this->assertSentToTippin();
    }

    /** @test */
    public function it_broadcast_to_all_in_thread()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe, $this->developers);
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        app(BroadcastBroker::class)
            ->toAllInThread($group)
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertDispatched(function (FakeBroadcastEvent $event) {
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.company.'.$this->developers->getKey(), $event->broadcastOn());
            $this->assertSame(1234, $event->broadcastWith()['data']);

            return true;
        });
    }

    /** @test */
    public function it_chunks_private_channels_at_100_sending_multiple_broadcast()
    {
        $group = Thread::factory()->group()->create();
        Event::fake([
            FakeBroadcastEvent::class,
        ]);
        Participant::factory()
            ->for($group)
            ->owner($this->tippin)
            ->admin()
            ->create();

        //Generate 300 unique participants.
        for ($x = 1; $x <= 150; $x++) {
            Participant::factory()
                ->for($group)
                ->owner(UserModel::factory()->create())
                ->create();
            Participant::factory()
                ->for($group)
                ->owner(CompanyModel::factory()->create())
                ->create();
        }

        app(BroadcastBroker::class)
            ->toAllInThread($group)
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        // Group has 301 total participants. 100 per === 4 total chunks.
        Event::assertDispatchedTimes(FakeBroadcastEvent::class, 4);
    }

    /** @test */
    public function it_chunks_presence_channels_at_100_sending_multiple_broadcast()
    {
        Event::fake([
            FakeBroadcastEvent::class,
        ]);
        //Generate 301 threads.
        Thread::factory()->count(301)->create();

        app(BroadcastBroker::class)
            ->toManyPresence(Thread::all())
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        // 301 threads. 100 per === 4 total chunks.
        Event::assertDispatchedTimes(FakeBroadcastEvent::class, 4);
    }

    /** @test */
    public function it_broadcast_to_others_in_thread()
    {
        $group = $this->createGroupThread($this->tippin, $this->doe, $this->developers);
        Messenger::setProvider($this->tippin);
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        app(BroadcastBroker::class)
            ->toOthersInThread($group)
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertDispatched(function (FakeBroadcastEvent $event) {
            $this->assertNotContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.company.'.$this->developers->getKey(), $event->broadcastOn());
            $this->assertSame(1234, $event->broadcastWith()['data']);

            return true;
        });
    }

    /** @test */
    public function it_broadcast_to_user()
    {
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        app(BroadcastBroker::class)
            ->to($this->tippin)
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        $this->assertSentToTippin();
    }

    /** @test */
    public function it_broadcast_to_company()
    {
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        app(BroadcastBroker::class)
            ->to($this->developers)
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertDispatched(function (FakeBroadcastEvent $event) {
            $this->assertContains('private-messenger.company.'.$this->developers->getKey(), $event->broadcastOn());
            $this->assertCount(1, $event->broadcastOn());
            $this->assertSame(1234, $event->broadcastWith()['data']);

            return true;
        });
    }

    /** @test */
    public function it_broadcast_to_selected_providers()
    {
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        app(BroadcastBroker::class)
            ->toSelected(collect([
                $this->tippin,
                $this->developers,
            ]))
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertDispatched(function (FakeBroadcastEvent $event) {
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.company.'.$this->developers->getKey(), $event->broadcastOn());
            $this->assertCount(2, $event->broadcastOn());
            $this->assertSame(1234, $event->broadcastWith()['data']);

            return true;
        });
    }

    /** @test */
    public function it_broadcast_to_thread_presence()
    {
        $thread = Thread::factory()->create();
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        app(BroadcastBroker::class)
            ->toPresence($thread)
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertDispatched(function (FakeBroadcastEvent $event) use ($thread) {
            $this->assertContains('presence-messenger.thread.'.$thread->id, $event->broadcastOn());
            $this->assertCount(1, $event->broadcastOn());
            $this->assertSame(1234, $event->broadcastWith()['data']);

            return true;
        });
    }

    /** @test */
    public function it_broadcast_to_call_presence()
    {
        $thread = Thread::factory()->create();
        $call = Call::factory()->for($thread)->owner($this->tippin)->create();
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        app(BroadcastBroker::class)
            ->toPresence($call)
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertDispatched(function (FakeBroadcastEvent $event) use ($call, $thread) {
            $this->assertContains("presence-messenger.call.{$call->id}.thread.{$thread->id}", $event->broadcastOn());
            $this->assertCount(1, $event->broadcastOn());
            $this->assertSame(1234, $event->broadcastWith()['data']);

            return true;
        });
    }

    /** @test */
    public function it_broadcast_to_many_presence()
    {
        $thread = Thread::factory()->create();
        $call = Call::factory()->for($thread)->owner($this->tippin)->create();
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        app(BroadcastBroker::class)
            ->toManyPresence(collect([
                $call,
                $thread,
            ]))
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertDispatched(function (FakeBroadcastEvent $event) use ($call, $thread) {
            $this->assertContains("presence-messenger.call.{$call->id}.thread.{$thread->id}", $event->broadcastOn());
            $this->assertContains('presence-messenger.thread.'.$thread->id, $event->broadcastOn());
            $this->assertCount(2, $event->broadcastOn());
            $this->assertSame(1234, $event->broadcastWith()['data']);

            return true;
        });
    }

    /** @test */
    public function it_broadcast_after_removing_duplicate_private_channels()
    {
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        app(BroadcastBroker::class)
            ->toSelected(collect([
                $this->tippin,
                $this->developers,
                $this->tippin,
                $this->developers,
                $this->doe,
            ]))
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertDispatched(function (FakeBroadcastEvent $event) {
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.company.'.$this->developers->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertCount(3, $event->broadcastOn());

            return true;
        });
    }

    /** @test */
    public function it_broadcast_after_removing_duplicate_presence_channels()
    {
        $thread = Thread::factory()->create();
        $call = Call::factory()->for($thread)->owner($this->tippin)->create();
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        app(BroadcastBroker::class)
            ->toManyPresence(collect([
                $call,
                $thread,
                $call,
                $thread,
            ]))
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertDispatched(function (FakeBroadcastEvent $event) use ($call, $thread) {
            $this->assertContains("presence-messenger.call.{$call->id}.thread.{$thread->id}", $event->broadcastOn());
            $this->assertContains('presence-messenger.thread.'.$thread->id, $event->broadcastOn());
            $this->assertCount(2, $event->broadcastOn());

            return true;
        });
    }

    /**
     * @test
     *
     * @dataProvider modelsWithOwner
     *
     * @param  $model
     */
    public function it_broadcast_to_ownerable_models_owner($model)
    {
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        app(BroadcastBroker::class)
            ->to($model($this->tippin))
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        $this->assertSentToTippin();
    }

    private function assertSentToTippin(): void
    {
        Event::assertDispatched(function (FakeBroadcastEvent $event) {
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertCount(1, $event->broadcastOn());
            $this->assertSame(1234, $event->broadcastWith()['data']);

            return true;
        });
    }
}

class FakeBroadcastEvent extends MessengerBroadcast
{
    public function broadcastAs(): string
    {
        return 'fake.broadcast';
    }
}

class InvalidBroadcastEvent implements ShouldBroadcastNow
{
    public function broadcastAs(): string
    {
        return 'bad.broadcast';
    }

    public function broadcastOn(): array
    {
        return [];
    }
}
