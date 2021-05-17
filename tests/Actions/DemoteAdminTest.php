<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\DemoteAdmin;
use RTippin\Messenger\Broadcasting\DemotedAdminBroadcast;
use RTippin\Messenger\Events\DemotedAdminEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Listeners\DemotedAdminMessage;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Tests\FeatureTestCase;

class DemoteAdminTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_updates_participant_permissions()
    {
        $thread = $this->createGroupThread($this->tippin);
        $participant = Participant::factory()->for($thread)->owner($this->doe)->admin()->create();

        app(DemoteAdmin::class)->execute($thread, $participant);

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
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
        BaseMessengerAction::enableEvents();
        Event::fake([
            DemotedAdminBroadcast::class,
            DemotedAdminEvent::class,
        ]);
        $thread = $this->createGroupThread($this->tippin);
        $participant = Participant::factory()->for($thread)->owner($this->doe)->admin()->create();

        app(DemoteAdmin::class)->execute($thread, $participant);

        Event::assertDispatched(function (DemotedAdminBroadcast $event) use ($thread) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($thread->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (DemotedAdminEvent $event) use ($thread, $participant) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($thread->id, $event->thread->id);
            $this->assertSame($participant->id, $event->participant->id);

            return true;
        });
    }

    /** @test */
    public function it_dispatches_listeners()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        $thread = $this->createGroupThread($this->tippin);
        $participant = Participant::factory()->for($thread)->owner($this->doe)->admin()->create();

        app(DemoteAdmin::class)->execute($thread, $participant);

        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === DemotedAdminMessage::class;
        });
    }
}
