<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\StoreManyParticipants;
use RTippin\Messenger\Broadcasting\NewThreadBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\ParticipantsAddedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Listeners\ParticipantsAddedMessage;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\Definitions;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreManyParticipantsTest extends FeatureTestCase
{
    private Thread $group;
    private MessengerProvider $tippin;
    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->doe = $this->userDoe();
        $this->group = $this->createGroupThread($this->tippin);
        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_ignores_non_friend()
    {
        app(StoreManyParticipants::class)->withoutDispatches()->execute(
            $this->group,
            [
                [
                    'id' => $this->doe->getKey(),
                    'alias' => 'user',
                ],
            ],
        );

        $this->assertDatabaseCount('participants', 1);
    }

    /** @test */
    public function it_ignores_not_found_provider()
    {
        app(StoreManyParticipants::class)->withoutDispatches()->execute(
            $this->group,
            [
                [
                    'id' => 404,
                    'alias' => 'user',
                ],
            ],
        );

        $this->assertDatabaseCount('participants', 1);
    }

    /** @test */
    public function it_fires_no_events_if_no_valid_providers()
    {
        $this->doesntExpectEvents([
            NewThreadBroadcast::class,
            ParticipantsAddedEvent::class,
        ]);

        app(StoreManyParticipants::class)->execute(
            $this->group,
            [],
        );

        $this->assertDatabaseCount('participants', 1);
    }

    /** @test */
    public function it_ignores_existing_participant()
    {
        $this->group->participants()->create(array_merge(Definitions::DefaultParticipant, [
            'owner_id' => $this->doe->getKey(),
            'owner_type' => get_class($this->doe),
        ]));
        $this->createFriends($this->tippin, $this->doe);

        $this->doesntExpectEvents([
            NewThreadBroadcast::class,
            ParticipantsAddedEvent::class,
        ]);

        app(StoreManyParticipants::class)->execute(
            $this->group,
            [
                [
                    'id' => $this->doe->getKey(),
                    'alias' => 'user',
                ],
            ],
        );

        $this->assertDatabaseCount('participants', 2);
    }

    /** @test */
    public function it_restores_participant_if_previously_soft_deleted()
    {
        $participant = $this->group->participants()->create(array_merge(Definitions::DefaultParticipant, [
            'owner_id' => $this->doe->getKey(),
            'owner_type' => get_class($this->doe),
            'deleted_at' => now(),
        ]));
        $this->createFriends($this->tippin, $this->doe);

        app(StoreManyParticipants::class)->withoutDispatches()->execute(
            $this->group,
            [
                [
                    'id' => $this->doe->getKey(),
                    'alias' => 'user',
                ],
            ],
        );

        $this->assertDatabaseCount('participants', 2);
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_stores_participants()
    {
        $developers = $this->companyDevelopers();
        $this->createFriends($this->tippin, $this->doe);
        $this->createFriends($this->tippin, $developers);

        app(StoreManyParticipants::class)->withoutDispatches()->execute(
            $this->group,
            [
                [
                    'id' => $this->doe->getKey(),
                    'alias' => 'user',
                ],
                [
                    'id' => $developers->getKey(),
                    'alias' => 'company',
                ],
            ],
        );

        $this->assertDatabaseCount('participants', 3);
        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->doe->getKey(),
            'owner_type' => get_class($this->doe),
            'admin' => false,
        ]);
        $this->assertDatabaseHas('participants', [
            'owner_id' => $developers->getKey(),
            'owner_type' => get_class($developers),
            'admin' => false,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        Event::fake([
            NewThreadBroadcast::class,
            ParticipantsAddedEvent::class,
        ]);
        $developers = $this->companyDevelopers();
        $this->createFriends($this->tippin, $this->doe);
        $this->createFriends($this->tippin, $developers);

        app(StoreManyParticipants::class)->execute(
            $this->group,
            [
                [
                    'id' => $this->doe->getKey(),
                    'alias' => 'user',
                ],
                [
                    'id' => $developers->getKey(),
                    'alias' => 'company',
                ],
            ],
        );

        Event::assertDispatched(function (NewThreadBroadcast $event) use ($developers) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.company.'.$developers->getKey(), $event->broadcastOn());
            $this->assertContains('First Test Group', $event->broadcastWith()['thread']);

            return true;
        });
        Event::assertDispatched(function (ParticipantsAddedEvent $event) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame('First Test Group', $event->thread->subject);
            $this->assertCount(2, $event->participants);

            return true;
        });
    }

    /** @test */
    public function it_dispatches_listeners()
    {
        Bus::fake();
        $this->createFriends($this->tippin, $this->doe);

        app(StoreManyParticipants::class)->withoutBroadcast()->execute(
            $this->group,
            [
                [
                    'id' => $this->doe->getKey(),
                    'alias' => 'user',
                ],
            ],
        );

        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === ParticipantsAddedMessage::class;
        });
    }
}
