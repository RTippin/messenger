<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\DemoteAdmin;
use RTippin\Messenger\Broadcasting\DemotedAdminBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Events\DemotedAdminEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class DemoteAdminTest extends FeatureTestCase
{
    private Thread $group;

    private Participant $participant;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->group = $this->createGroupThread($this->tippin);

        $this->participant = $this->group->participants()
            ->create(array_merge(Definitions::DefaultAdminParticipant, [
                'owner_id' => $this->doe->getKey(),
                'owner_type' => get_class($this->doe),
            ]));
    }

    /** @test */
    public function demote_admin_updates_participant()
    {
        app(DemoteAdmin::class)->withoutDispatches()
            ->execute($this->group, $this->participant);

        $this->assertDatabaseHas('participants', [
            'id' => $this->participant->id,
            'admin' => false,
        ]);
    }

    /** @test */
    public function demote_admin_fires_events()
    {
        Event::fake([
            DemotedAdminBroadcast::class,
            DemotedAdminEvent::class,
        ]);

        Messenger::setProvider($this->tippin);

        app(DemoteAdmin::class)->execute($this->group, $this->participant);

        Event::assertDispatched(function (DemotedAdminBroadcast $event) {
            $this->assertContains('private-user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (DemotedAdminEvent $event) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($this->group->id, $event->thread->id);
            $this->assertSame($this->participant->id, $event->participant->id);

            return true;
        });
    }
}
