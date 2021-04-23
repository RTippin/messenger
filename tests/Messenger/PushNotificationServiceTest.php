<?php

namespace RTippin\Messenger\Tests\Messenger;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\MessengerBroadcast;
use RTippin\Messenger\Events\PushNotificationEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\PushNotificationService;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\OtherModel;

class PushNotificationServiceTest extends FeatureTestCase
{
    private Thread $group;
    const WITH = [
        'data' => 1234,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->createGroupThread($this->tippin, $this->doe, $this->developers);
    }

    /** @test */
    public function it_doesnt_fire_event_if_empty_collection()
    {
        Event::fake([
            PushNotificationEvent::class,
        ]);

        app(PushNotificationService::class)
            ->to(collect())
            ->with(self::WITH)
            ->notify(FakeNotifyEvent::class);

        Event::assertNotDispatched(PushNotificationEvent::class);
    }

    /** @test */
    public function it_doesnt_fire_event_if_no_valid_providers()
    {
        Event::fake([
            PushNotificationEvent::class,
        ]);

        app(PushNotificationService::class)
            ->to(collect([
                new OtherModel,
            ]))
            ->with(self::WITH)
            ->notify(FakeNotifyEvent::class);

        Event::assertNotDispatched(PushNotificationEvent::class);
    }

    /** @test */
    public function it_fires_events_for_two_providers()
    {
        Event::fake([
            PushNotificationEvent::class,
        ]);

        app(PushNotificationService::class)
            ->to(collect([
                $this->tippin,
                $this->developers,
            ]))
            ->with(self::WITH)
            ->notify(FakeNotifyEvent::class);

        Event::assertDispatched(function (PushNotificationEvent $event) {
            $recipients = $event->recipients->toArray();
            $tippin = [
                'owner_type' => $this->tippin->getMorphClass(),
                'owner_id' => $this->tippin->getKey(),
            ];
            $developers = [
                'owner_type' => $this->developers->getMorphClass(),
                'owner_id' => $this->developers->getKey(),
            ];

            $this->assertContains($tippin, $recipients);
            $this->assertContains($developers, $recipients);
            $this->assertCount(2, $event->recipients);
            $this->assertSame('fake.notify', $event->broadcastAs);
            $this->assertSame(1234, $event->data['data']);

            return true;
        });
    }

    /** @test */
    public function it_fires_events_to_many_providers()
    {
        Event::fake([
            PushNotificationEvent::class,
        ]);
        $this->getModelUser()::factory()->count(100)->create();
        $this->getModelCompany()::factory()->count(100)->create();

        app(PushNotificationService::class)->to(
                $this->getModelUser()::get()
                    ->push($this->getModelCompany()::get())
                    ->values()
                    ->flatten()
            )
            ->with(self::WITH)
            ->notify(FakeNotifyEvent::class);

        Event::assertDispatched(function (PushNotificationEvent $event) {
            return $event->recipients->count() === 203;
        });
    }

    /** @test */
    public function it_ignores_provider_with_devices_disabled()
    {
        Event::fake([
            PushNotificationEvent::class,
        ]);

        $providers = $this->getBaseProvidersConfig();
        $providers['company']['devices'] = false;
        Messenger::setMessengerProviders($providers);

        app(PushNotificationService::class)
            ->to(collect([
                $this->tippin,
                $this->developers,
            ]))
            ->with(self::WITH)
            ->notify(FakeNotifyEvent::class);

        Event::assertDispatched(function (PushNotificationEvent $event) {
            $recipients = $event->recipients->toArray();
            $developers = [
                'owner_type' => $this->developers->getMorphClass(),
                'owner_id' => $this->developers->getKey(),
            ];

            $this->assertNotContains($developers, $recipients);
            $this->assertCount(1, $event->recipients);
            $this->assertSame('fake.notify', $event->broadcastAs);
            $this->assertSame(1234, $event->data['data']);

            return true;
        });
    }

    /** @test */
    public function it_fires_events_using_thread_participants()
    {
        Event::fake([
            PushNotificationEvent::class,
        ]);

        app(PushNotificationService::class)
            ->to($this->group->participants()->get())
            ->with(self::WITH)
            ->notify(FakeNotifyEvent::class);

        Event::assertDispatched(function (PushNotificationEvent $event) {
            $this->assertCount(3, $event->recipients);
            $this->assertSame('fake.notify', $event->broadcastAs);
            $this->assertSame(1234, $event->data['data']);

            return true;
        });
    }

    /** @test */
    public function it_fires_events_using_call_participants()
    {
        Event::fake([
            PushNotificationEvent::class,
        ]);

        app(PushNotificationService::class)
            ->to($this->createCall($this->group, $this->tippin, $this->doe)->participants()->get())
            ->with(self::WITH)
            ->notify(FakeNotifyEvent::class);

        Event::assertDispatched(function (PushNotificationEvent $event) {
            $this->assertCount(2, $event->recipients);
            $this->assertSame('fake.notify', $event->broadcastAs);
            $this->assertSame(1234, $event->data['data']);

            return true;
        });
    }

    /** @test */
    public function it_rejects_duplicate_matching_providers()
    {
        Event::fake([
            PushNotificationEvent::class,
        ]);

        app(PushNotificationService::class)
            ->to(collect([
                $this->tippin,
                $this->developers,
                $this->tippin,
                $this->developers,
                $this->group->participants()->admins()->first(),
            ]))
            ->with(self::WITH)
            ->notify(FakeNotifyEvent::class);

        Event::assertDispatched(function (PushNotificationEvent $event) {
            $this->assertCount(2, $event->recipients);
            $this->assertSame('fake.notify', $event->broadcastAs);
            $this->assertSame(1234, $event->data['data']);

            return true;
        });
    }
}

class FakeNotifyEvent extends MessengerBroadcast
{
    public function broadcastAs(): string
    {
        return 'fake.notify';
    }
}
