<?php

namespace RTippin\Messenger\Tests\Messenger;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\MessengerBroadcast;
use RTippin\Messenger\Brokers\BroadcastBroker;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class BroadcastDriverTest extends FeatureTestCase
{
    private Thread $group;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    private MessengerProvider $developers;

    const WITH = [
        'data' => 1234,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->developers = $this->companyDevelopers();

        $this->group = $this->createGroupThread($this->tippin, $this->doe, $this->developers);
    }

    /** @test */
    public function broadcast_driver_using_default_broadcast_broker()
    {
        $broadcaster = app(BroadcastDriver::class);

        $this->assertInstanceOf(BroadcastBroker::class, $broadcaster);
    }

    /** @test */
    public function broadcast_driver_ignores_invalid_event_passed()
    {
        Event::fake([
            InvalidBroadcastEvent::class,
        ]);

        app(BroadcastDriver::class)
            ->to($this->tippin)
            ->with(self::WITH)
            ->broadcast(InvalidBroadcastEvent::class);

        Event::assertNotDispatched(InvalidBroadcastEvent::class);
    }

    /** @test */
    public function broadcast_to_all_in_thread()
    {
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        app(BroadcastDriver::class)
            ->toAllInThread($this->group)
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertDispatched(function (FakeBroadcastEvent $event) {
            $this->assertContains('private-user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertContains('private-user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-company.'.$this->developers->getKey(), $event->broadcastOn());
            $this->assertSame(1234, $event->broadcastWith()['data']);

            return true;
        });
    }

    /** @test */
    public function broadcast_to_others_in_thread()
    {
        Messenger::setProvider($this->tippin);

        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        app(BroadcastDriver::class)
            ->toOthersInThread($this->group)
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertDispatched(function (FakeBroadcastEvent $event) {
            $this->assertNotContains('private-user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertContains('private-user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-company.'.$this->developers->getKey(), $event->broadcastOn());
            $this->assertSame(1234, $event->broadcastWith()['data']);

            return true;
        });
    }

    /** @test */
    public function broadcast_to_user()
    {
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        app(BroadcastDriver::class)
            ->to($this->tippin)
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertDispatched(function (FakeBroadcastEvent $event) {
            $this->assertContains('private-user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertCount(1, $event->broadcastOn());
            $this->assertSame(1234, $event->broadcastWith()['data']);

            return true;
        });
    }

    /** @test */
    public function broadcast_to_company()
    {
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        app(BroadcastDriver::class)
            ->to($this->developers)
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertDispatched(function (FakeBroadcastEvent $event) {
            $this->assertContains('private-company.'.$this->developers->getKey(), $event->broadcastOn());
            $this->assertCount(1, $event->broadcastOn());
            $this->assertSame(1234, $event->broadcastWith()['data']);

            return true;
        });
    }

    /** @test */
    public function broadcast_to_thread_participant()
    {
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        $participant = $this->group->participants()->admins()->first();

        app(BroadcastDriver::class)
            ->to($participant)
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertDispatched(function (FakeBroadcastEvent $event) {
            $this->assertContains('private-user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertCount(1, $event->broadcastOn());
            $this->assertSame(1234, $event->broadcastWith()['data']);

            return true;
        });
    }

    /** @test */
    public function broadcast_to_call_participant()
    {
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        $callParticipant = $this->createCall($this->group, $this->tippin)->participants()->first();

        app(BroadcastDriver::class)
            ->to($callParticipant)
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertDispatched(function (FakeBroadcastEvent $event) {
            $this->assertContains('private-user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertCount(1, $event->broadcastOn());
            $this->assertSame(1234, $event->broadcastWith()['data']);

            return true;
        });
    }

    /** @test */
    public function broadcast_to_selected_providers()
    {
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        $to = collect([
            $this->tippin,
            $this->developers,
        ]);

        app(BroadcastDriver::class)
            ->toSelected($to)
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertDispatched(function (FakeBroadcastEvent $event) {
            $this->assertContains('private-user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertContains('private-company.'.$this->developers->getKey(), $event->broadcastOn());
            $this->assertCount(2, $event->broadcastOn());
            $this->assertSame(1234, $event->broadcastWith()['data']);

            return true;
        });
    }

    /** @test */
    public function broadcast_to_thread_presence()
    {
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        app(BroadcastDriver::class)
            ->toPresence($this->group)
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertDispatched(function (FakeBroadcastEvent $event) {
            $this->assertContains('presence-thread.'.$this->group->id, $event->broadcastOn());
            $this->assertCount(1, $event->broadcastOn());
            $this->assertSame(1234, $event->broadcastWith()['data']);

            return true;
        });
    }

    /** @test */
    public function broadcast_to_call_presence()
    {
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        $call = $this->createCall($this->group, $this->tippin);

        app(BroadcastDriver::class)
            ->toPresence($call)
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertDispatched(function (FakeBroadcastEvent $event) use ($call) {
            $this->assertContains("presence-call.{$call->id}.thread.{$this->group->id}", $event->broadcastOn());
            $this->assertCount(1, $event->broadcastOn());
            $this->assertSame(1234, $event->broadcastWith()['data']);

            return true;
        });
    }

    /** @test */
    public function broadcast_to_many_presence()
    {
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        $call = $this->createCall($this->group, $this->tippin);

        $to = collect([
            $call,
            $this->group,
        ]);

        app(BroadcastDriver::class)
            ->toManyPresence($to)
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        Event::assertDispatched(function (FakeBroadcastEvent $event) use ($call) {
            $this->assertContains("presence-call.{$call->id}.thread.{$this->group->id}", $event->broadcastOn());
            $this->assertContains('presence-thread.'.$this->group->id, $event->broadcastOn());
            $this->assertCount(2, $event->broadcastOn());
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
