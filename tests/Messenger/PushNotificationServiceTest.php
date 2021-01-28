<?php

namespace RTippin\Messenger\Tests\Messenger;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\MessengerBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\PushNotificationEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\PushNotificationService;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\stubs\OtherModel;

class PushNotificationServiceTest extends FeatureTestCase
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
    public function notify_with_no_empty_collection_fires_no_event()
    {
        Event::fake([
            PushNotificationEvent::class,
        ]);

        $to = collect();

        app(PushNotificationService::class)
            ->to($to)
            ->with(self::WITH)
            ->notify(FakeNotifyEvent::class);

        Event::assertNotDispatched(PushNotificationEvent::class);
    }

    /** @test */
    public function notify_with_no_valid_recipients_fires_no_event()
    {
        Event::fake([
            PushNotificationEvent::class,
        ]);

        $to = collect([
            new OtherModel,
        ]);

        app(PushNotificationService::class)
            ->to($to)
            ->with(self::WITH)
            ->notify(FakeNotifyEvent::class);

        Event::assertNotDispatched(PushNotificationEvent::class);
    }

    /** @test */
    public function notify_two_provider_models()
    {
        Event::fake([
            PushNotificationEvent::class,
        ]);

        $to = collect([
            $this->tippin,
            $this->developers,
        ]);

        app(PushNotificationService::class)
            ->to($to)
            ->with(self::WITH)
            ->notify(FakeNotifyEvent::class);

        Event::assertDispatched(function (PushNotificationEvent $event) {
            $recipients = $event->recipients->toArray();

            $tippin = [
                'owner_type' => get_class($this->tippin),
                'owner_id' => $this->tippin->getKey(),
            ];

            $developers = [
                'owner_type' => get_class($this->developers),
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
}

class FakeNotifyEvent extends MessengerBroadcast
{
    public function broadcastAs(): string
    {
        return 'fake.notify';
    }
}
