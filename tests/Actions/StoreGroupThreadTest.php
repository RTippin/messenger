<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\StoreGroupThread;
use RTippin\Messenger\Broadcasting\NewThreadBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\NewThreadEvent;
use RTippin\Messenger\Events\ParticipantsAddedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreGroupThreadTest extends FeatureTestCase
{
    private MessengerProvider $tippin;
    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->doe = $this->userDoe();
        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_stores_thread_and_participant()
    {
        app(StoreGroupThread::class)->withoutDispatches()->execute([
            'subject' => 'Test Group',
        ]);

        $this->assertDatabaseCount('participants', 1);
        $this->assertDatabaseCount('threads', 1);
        $this->assertDatabaseHas('threads', [
            'subject' => 'Test Group',
        ]);
        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'admin' => true,
        ]);
    }

    /** @test */
    public function it_stores_system_message()
    {
        app(StoreGroupThread::class)->withoutDispatches()->execute([
            'subject' => 'Test Group',
        ]);

        $this->assertDatabaseCount('messages', 1);
        $this->assertDatabaseHas('messages', [
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'type' => 93,
            'body' => 'created Test Group',
        ]);
    }

    /** @test */
    public function it_stores_added_participant()
    {
        $this->createFriends($this->tippin, $this->doe);

        app(StoreGroupThread::class)->withoutDispatches()->execute([
            'subject' => 'Test Group',
            'providers' => [
                [
                    'id' => $this->doe->getKey(),
                    'alias' => 'user',
                ],
            ],
        ]);

        $this->assertDatabaseCount('participants', 2);
        $this->assertDatabaseCount('threads', 1);
        $this->assertDatabaseHas('threads', [
            'subject' => 'Test Group',
        ]);
    }

    /** @test */
    public function it_ignores_participant_if_not_friend()
    {
        app(StoreGroupThread::class)->withoutDispatches()->execute([
            'subject' => 'Test Group',
            'providers' => [
                [
                    'id' => $this->doe->getKey(),
                    'alias' => 'user',
                ],
            ],
        ]);

        $this->assertDatabaseCount('participants', 1);
        $this->assertDatabaseCount('threads', 1);
    }

    /** @test */
    public function it_fires_events_without_extra_participants()
    {
        $this->expectsEvents([
            NewThreadEvent::class,
        ]);
        $this->doesntExpectEvents([
            NewThreadBroadcast::class,
        ]);

        app(StoreGroupThread::class)->execute([
            'subject' => 'Test Group',
        ]);
    }

    /** @test */
    public function it_fires_events_with_extra_participants()
    {
        Event::fake([
            NewThreadEvent::class,
            NewThreadBroadcast::class,
            ParticipantsAddedEvent::class,
        ]);
        $developers = $this->companyDevelopers();
        $this->createFriends($this->tippin, $this->doe);
        $this->createFriends($this->tippin, $developers);

        app(StoreGroupThread::class)->execute([
            'subject' => 'Test Many Participants',
            'providers' => [
                [
                    'id' => $this->doe->getKey(),
                    'alias' => 'user',
                ],
                [
                    'id' => $developers->getKey(),
                    'alias' => 'company',
                ],
            ],
        ]);

        Event::assertDispatched(function (NewThreadEvent $event) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame('Test Many Participants', $event->thread->subject);

            return true;
        });
        Event::assertDispatched(NewThreadBroadcast::class);
        Event::assertDispatched(ParticipantsAddedEvent::class);
    }
}
