<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\DemoteAdmin;
use RTippin\Messenger\Broadcasting\DemotedAdminBroadcast;
use RTippin\Messenger\Events\DemotedAdminEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Listeners\DemotedAdminMessage;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class DemoteAdminTest extends FeatureTestCase
{
    private Thread $group;
    private Participant $participant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->createGroupThread($this->tippin);
        $this->participant = Participant::factory()->for($this->group)->owner($this->doe)->admin()->create();
        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_updates_participant_permissions()
    {
        app(DemoteAdmin::class)->withoutDispatches()->execute(
            $this->group,
            $this->participant
        );

        $this->assertDatabaseHas('participants', [
            'id' => $this->participant->id,
            'admin' => false,
            'add_participants' => false,
            'manage_invites' => false,
            'start_calls' => false,
            'send_knocks' => false,
            'send_messages' => true,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        Event::fake([
            DemotedAdminBroadcast::class,
            DemotedAdminEvent::class,
        ]);

        app(DemoteAdmin::class)->execute(
            $this->group,
            $this->participant
        );

        Event::assertDispatched(function (DemotedAdminBroadcast $event) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
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

    /** @test */
    public function it_dispatches_listeners()
    {
        Bus::fake();

        app(DemoteAdmin::class)->withoutBroadcast()->execute(
            $this->group,
            $this->participant
        );

        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === DemotedAdminMessage::class;
        });
    }
}
