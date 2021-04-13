<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\PromoteAdmin;
use RTippin\Messenger\Broadcasting\PromotedAdminBroadcast;
use RTippin\Messenger\Events\PromotedAdminEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Listeners\PromotedAdminMessage;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PromoteAdminTest extends FeatureTestCase
{
    private Thread $group;
    private Participant $participant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->createGroupThread($this->tippin);
        $this->participant = Participant::factory()->for($this->group)->owner($this->doe)->create();
        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_updates_participant_permissions()
    {
        app(PromoteAdmin::class)->withoutDispatches()->execute(
            $this->group,
            $this->participant
        );

        $this->assertDatabaseHas('participants', [
            'id' => $this->participant->id,
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
        Event::fake([
            PromotedAdminBroadcast::class,
            PromotedAdminEvent::class,
        ]);

        app(PromoteAdmin::class)->execute(
            $this->group,
            $this->participant
        );

        Event::assertDispatched(function (PromotedAdminBroadcast $event) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (PromotedAdminEvent $event) {
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

        app(PromoteAdmin::class)->withoutBroadcast()->execute(
            $this->group,
            $this->participant
        );

        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === PromotedAdminMessage::class;
        });
    }
}
