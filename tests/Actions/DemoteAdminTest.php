<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\DemoteAdmin;
use RTippin\Messenger\Broadcasting\DemotedAdminBroadcast;
use RTippin\Messenger\Events\DemotedAdminEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\DemotedAdminMessage;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class DemoteAdminTest extends FeatureTestCase
{
    use BroadcastLogger;

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
        $this->logBroadcast(DemotedAdminBroadcast::class);
    }

    /** @test */
    public function it_dispatches_subscriber_job()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        $thread = $this->createGroupThread($this->tippin);
        $participant = Participant::factory()->for($thread)->owner($this->doe)->admin()->create();

        app(DemoteAdmin::class)->execute($thread, $participant);

        Bus::assertDispatched(DemotedAdminMessage::class);
    }

    /** @test */
    public function it_runs_subscriber_job_now()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('queued', false);
        $thread = $this->createGroupThread($this->tippin);
        $participant = Participant::factory()->for($thread)->owner($this->doe)->admin()->create();

        app(DemoteAdmin::class)->execute($thread, $participant);

        Bus::assertDispatchedSync(DemotedAdminMessage::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_job_if_disabled()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('enabled', false);
        $thread = $this->createGroupThread($this->tippin);
        $participant = Participant::factory()->for($thread)->owner($this->doe)->admin()->create();

        app(DemoteAdmin::class)->execute($thread, $participant);

        Bus::assertNotDispatched(DemotedAdminMessage::class);
    }
}
