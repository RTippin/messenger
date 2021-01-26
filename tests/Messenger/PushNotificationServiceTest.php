<?php

namespace RTippin\Messenger\Tests\Messenger;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Broadcasting\MessengerBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\PushNotificationEvent;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\PushNotificationService;
use RTippin\Messenger\Tests\FeatureTestCase;

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
    public function notify_with_no_valid_recipients_fires_no_event()
    {
        Event::fake([
            PushNotificationEvent::class,
        ]);

        app(PushNotificationService::class)
            ->to(collect([]))
            ->with(self::WITH)
            ->notify(FakeNotifyEvent::class);

        Event::assertNotDispatched(PushNotificationEvent::class);
    }
}

class FakeNotifyEvent extends MessengerBroadcast
{
    public function broadcastAs(): string
    {
        return 'fake.notify';
    }
}
