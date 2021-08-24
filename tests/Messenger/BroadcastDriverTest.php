<?php

namespace RTippin\Messenger\Tests\Messenger;

use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\MessengerBroadcast;
use RTippin\Messenger\Brokers\BroadcastBroker;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\PushNotificationEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\BotAction;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Friend;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\CompanyModel;
use RTippin\Messenger\Tests\Fixtures\OtherModel;
use RTippin\Messenger\Tests\Fixtures\UserModel;

class BroadcastDriverTest extends FeatureTestCase
{
    const WITH = [
        'data' => 1234,
    ];

    /** @test */
    public function it_uses_default_broadcast_broker()
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

        app(BroadcastDriver::class)
            ->to($this->tippin)
            ->with(self::WITH)
            ->broadcast(InvalidBroadcastEvent::class);

        Event::assertNotDispatched(InvalidBroadcastEvent::class);
    }

    /** @test */
    public function it_doesnt_fire_events_if_no_recipients()
    {
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        app(BroadcastDriver::class)
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

        app(BroadcastDriver::class)
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

        app(BroadcastDriver::class)
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

        app(BroadcastDriver::class)
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
    public function it_chunks_channels_at_100_sending_multiple_broadcast()
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
        Participant::factory()
            ->for($group)
            ->owner(UserModel::factory()->create())
            ->count(200)
            ->create();
        Participant::factory()
            ->for($group)
            ->owner(CompanyModel::factory()->create())
            ->count(100)
            ->create();

        app(BroadcastDriver::class)
            ->toAllInThread($group)
            ->with(self::WITH)
            ->broadcast(FakeBroadcastEvent::class);

        // Group has 301 total participants. 100 per === 4 total chunks.
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

        app(BroadcastDriver::class)
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

        app(BroadcastDriver::class)
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

        app(BroadcastDriver::class)
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

        app(BroadcastDriver::class)
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

        app(BroadcastDriver::class)
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

        app(BroadcastDriver::class)
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

        app(BroadcastDriver::class)
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

    /**
     * @test
     * @dataProvider modelsWithOwner
     * @param $model
     */
    public function it_broadcast_to_model_owner($model)
    {
        Event::fake([
            FakeBroadcastEvent::class,
        ]);

        app(BroadcastDriver::class)
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

    public function modelsWithOwner(): array
    {
        return [
            'Bot Model' => [
                fn ($tippin) => Bot::factory()
                    ->for(Thread::factory()->create())
                    ->owner($tippin)
                    ->create()
            ],
            'BotAction Model' => [
                fn ($tippin) => BotAction::factory()
                    ->for(
                        Bot::factory()->for(
                            Thread::factory()->group()->create()
                        )->owner($tippin)->create()
                    )->owner($tippin)->create()
            ],
            'Call Model' => [
                fn ($tippin) => Call::factory()
                    ->for(Thread::factory()->create())
                    ->owner($tippin)
                    ->create()
            ],
            'CallParticipant Model' => [
                fn ($tippin) => CallParticipant::factory()
                    ->for(
                        Call::factory()->for(
                            Thread::factory()->create()
                        )->owner($tippin)->create()
                    )->owner($tippin)->create()
            ],
            'Friend Model' => [
                fn ($tippin) => Friend::factory()
                    ->providers($tippin, $tippin)
                    ->create()
            ],
            'Invite Model' => [
                fn ($tippin) => Invite::factory()
                    ->for(Thread::factory()->group()->create())
                    ->owner($tippin)
                    ->create()
            ],
            'Message Model' => [
                fn ($tippin) => Message::factory()
                    ->for(Thread::factory()->create())
                    ->owner($tippin)
                    ->create()
            ],
            'MessageReaction Model' => [
                fn ($tippin) => MessageReaction::factory()
                    ->for(
                        Message::factory()->for(
                            Thread::factory()->create()
                        )->owner($tippin)->create()
                    )->owner($tippin)->create()
            ],
            'Messenger Model' => [
                fn ($tippin) => \RTippin\Messenger\Models\Messenger::factory()
                    ->owner($tippin)
                    ->create()
            ],
            'Participant Model' => [
                fn ($tippin) => Participant::factory()
                    ->for(Thread::factory()->create())
                    ->owner($tippin)
                    ->admin()
                    ->create()
            ],
        ];
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
