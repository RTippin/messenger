<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\PromoteAdmin;
use RTippin\Messenger\Broadcasting\PromotedAdminBroadcast;
use RTippin\Messenger\Events\PromotedAdminEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\PromotedAdminMessage;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class PromoteAdminTest extends FeatureTestCase
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
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();

        app(PromoteAdmin::class)->execute($thread, $participant);

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'add_participants' => true,
            'manage_invites' => true,
            'admin' => true,
            'start_calls' => true,
            'send_knocks' => true,
            'send_messages' => true,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            PromotedAdminBroadcast::class,
            PromotedAdminEvent::class,
        ]);
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();

        app(PromoteAdmin::class)->execute($thread, $participant);

        Event::assertDispatched(function (PromotedAdminBroadcast $event) use ($thread) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($thread->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (PromotedAdminEvent $event) use ($thread, $participant) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($thread->id, $event->thread->id);
            $this->assertSame($participant->id, $event->participant->id);

            return true;
        });
        $this->logBroadcast(PromotedAdminBroadcast::class);
    }

    /** @test */
    public function it_dispatches_subscriber_job()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();

        app(PromoteAdmin::class)->execute($thread, $participant);

        Bus::assertDispatched(PromotedAdminMessage::class);
    }

    /** @test */
    public function it_runs_subscriber_job_now()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('queued', false);
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();

        app(PromoteAdmin::class)->execute($thread, $participant);

        Bus::assertDispatchedSync(PromotedAdminMessage::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_job_if_disabled()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('enabled', false);
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->create();

        app(PromoteAdmin::class)->execute($thread, $participant);

        Bus::assertNotDispatched(PromotedAdminMessage::class);
    }
}
