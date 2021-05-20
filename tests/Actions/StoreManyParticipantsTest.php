<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\StoreManyParticipants;
use RTippin\Messenger\Broadcasting\NewThreadBroadcast;
use RTippin\Messenger\Events\ParticipantsAddedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Listeners\ParticipantsAddedMessage;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreManyParticipantsTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_ignores_non_friend()
    {
        $thread = $this->createGroupThread($this->tippin);

        app(StoreManyParticipants::class)->execute($thread, [
            [
                'id' => $this->doe->getKey(),
                'alias' => 'user',
            ],
        ]);

        $this->assertDatabaseCount('participants', 1);
    }

    /** @test */
    public function it_ignores_not_found_provider()
    {
        $thread = $this->createGroupThread($this->tippin);

        app(StoreManyParticipants::class)->execute($thread, [
            [
                'id' => 404,
                'alias' => 'user',
            ],
        ]);

        $this->assertDatabaseCount('participants', 1);
    }

    /** @test */
    public function it_fires_no_events_if_no_valid_providers()
    {
        BaseMessengerAction::enableEvents();
        $thread = $this->createGroupThread($this->tippin);

        $this->doesntExpectEvents([
            NewThreadBroadcast::class,
            ParticipantsAddedEvent::class,
        ]);

        app(StoreManyParticipants::class)->execute($thread, []);

        $this->assertDatabaseCount('participants', 1);
    }

    /** @test */
    public function it_ignores_existing_participant()
    {
        BaseMessengerAction::enableEvents();
        $thread = $this->createGroupThread($this->tippin);
        Participant::factory()->for($thread)->owner($this->doe)->create();
        $this->createFriends($this->tippin, $this->doe);

        app(StoreManyParticipants::class)->execute($thread, [
            [
                'id' => $this->doe->getKey(),
                'alias' => 'user',
            ],
        ]);

        $this->assertDatabaseCount('participants', 2);
    }

    /** @test */
    public function it_restores_participant_if_previously_soft_deleted()
    {
        $thread = $this->createGroupThread($this->tippin);
        $participant = Participant::factory()->for($thread)->owner($this->doe)->trashed()->create();
        $this->createFriends($this->tippin, $this->doe);

        app(StoreManyParticipants::class)->execute($thread, [
            [
                'id' => $this->doe->getKey(),
                'alias' => 'user',
            ],
        ]);

        $this->assertDatabaseCount('participants', 2);
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_stores_participants()
    {
        $thread = $this->createGroupThread($this->tippin);
        $this->createFriends($this->tippin, $this->doe);
        $this->createFriends($this->tippin, $this->developers);

        app(StoreManyParticipants::class)->execute($thread, [
            [
                'id' => $this->doe->getKey(),
                'alias' => 'user',
            ],
            [
                'id' => $this->developers->getKey(),
                'alias' => 'company',
            ],
        ]);

        $this->assertDatabaseCount('participants', 3);
        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->doe->getKey(),
            'owner_type' => $this->doe->getMorphClass(),
            'admin' => false,
        ]);
        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->developers->getKey(),
            'owner_type' => $this->developers->getMorphClass(),
            'admin' => false,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewThreadBroadcast::class,
            ParticipantsAddedEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);
        $this->createFriends($this->tippin, $this->doe);
        $this->createFriends($this->tippin, $this->developers);

        app(StoreManyParticipants::class)->execute($thread, [
            [
                'id' => $this->doe->getKey(),
                'alias' => 'user',
            ],
            [
                'id' => $this->developers->getKey(),
                'alias' => 'company',
            ],
        ]);

        Event::assertDispatched(function (NewThreadBroadcast $event) use ($thread) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.company.'.$this->developers->getKey(), $event->broadcastOn());
            $this->assertSame($thread->id, $event->broadcastWith()['thread']['id']);

            return true;
        });
        Event::assertDispatched(function (ParticipantsAddedEvent $event) use ($thread) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($thread->id, $event->thread->id);
            $this->assertCount(2, $event->participants);

            return true;
        });
    }

    /** @test */
    public function it_dispatches_listeners()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        $this->createFriends($this->tippin, $this->doe);

        app(StoreManyParticipants::class)->execute(Thread::factory()->group()->create(), [
            [
                'id' => $this->doe->getKey(),
                'alias' => 'user',
            ],
        ]);

        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === ParticipantsAddedMessage::class;
        });
    }
}
