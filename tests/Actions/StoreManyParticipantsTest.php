<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\StoreManyParticipants;
use RTippin\Messenger\Broadcasting\NewThreadBroadcast;
use RTippin\Messenger\Events\ParticipantsAddedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\ParticipantsAddedMessage;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\Fixtures\UserModel;

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
    public function it_ignores_adding_participants_if_set_provider_not_friendable()
    {
        UserModel::$friendable = false;
        Messenger::registerProviders([UserModel::class]);
        Messenger::setProvider($this->tippin);
        $thread = $this->createGroupThread($this->tippin);
        $this->createFriends($this->tippin, $this->doe);

        app(StoreManyParticipants::class)->execute($thread, [
            [
                'id' => $this->doe->getKey(),
                'alias' => 'user',
            ],
        ]);

        $this->assertDatabaseCount('participants', 1);
    }

    /** @test */
    public function it_adds_non_friend_if_friendship_verification_disabled()
    {
        Messenger::setVerifyGroupThreadFriendship(false);
        $thread = $this->createGroupThread($this->tippin);

        app(StoreManyParticipants::class)->execute($thread, [
            [
                'id' => $this->doe->getKey(),
                'alias' => 'user',
            ],
        ]);

        $this->assertDatabaseCount('participants', 2);
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
    public function it_ignores_existing_participant()
    {
        Messenger::setVerifyGroupThreadFriendship(false);
        $thread = $this->createGroupThread($this->tippin, $this->doe);

        $this->assertDatabaseCount('participants', 2);

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
    public function it_fires_no_events_if_no_valid_providers()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewThreadBroadcast::class,
            ParticipantsAddedEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);

        app(StoreManyParticipants::class)->execute($thread, []);

        $this->assertDatabaseCount('participants', 1);
        Event::assertNotDispatched(NewThreadBroadcast::class);
        Event::assertNotDispatched(ParticipantsAddedEvent::class);
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
    public function it_dispatches_subscriber_job()
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

        Bus::assertDispatched(ParticipantsAddedMessage::class);
    }

    /** @test */
    public function it_runs_subscriber_job_now()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('queued', false);
        $this->createFriends($this->tippin, $this->doe);

        app(StoreManyParticipants::class)->execute(Thread::factory()->group()->create(), [
            [
                'id' => $this->doe->getKey(),
                'alias' => 'user',
            ],
        ]);

        Bus::assertDispatchedSync(ParticipantsAddedMessage::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_job_if_disabled()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('enabled', false);
        $this->createFriends($this->tippin, $this->doe);

        app(StoreManyParticipants::class)->execute(Thread::factory()->group()->create(), [
            [
                'id' => $this->doe->getKey(),
                'alias' => 'user',
            ],
        ]);

        Bus::assertNotDispatched(ParticipantsAddedMessage::class);
    }
}
