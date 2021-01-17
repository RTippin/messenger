<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\PromoteAdmin;
use RTippin\Messenger\Broadcasting\PromotedAdminBroadcast;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Events\PromotedAdminEvent;
use RTippin\Messenger\Facades\Messenger;
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

        $doe = $this->userDoe();

        $this->group = $this->createGroupThread($this->userTippin());

        $this->participant = $this->group->participants()
            ->create(array_merge(Definitions::DefaultParticipant, [
                'owner_id' => $doe->getKey(),
                'owner_type' => get_class($doe),
            ]));
    }

    /** @test */
    public function promote_admin_updates_participant()
    {
        app(PromoteAdmin::class)->withoutDispatches()
            ->execute($this->group, $this->participant);

        $this->assertDatabaseHas('participants', [
            'id' => $this->participant->id,
            'admin' => true,
        ]);
    }

    /** @test */
    public function demote_admin_fires_events()
    {
        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        Event::fake([
            PromotedAdminBroadcast::class,
            PromotedAdminEvent::class,
        ]);

        Messenger::setProvider($tippin);

        app(PromoteAdmin::class)->execute($this->group, $this->participant);

        Event::assertDispatched(function (PromotedAdminBroadcast $event) use ($doe) {
            $this->assertContains('private-user.'.$doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (PromotedAdminEvent $event) use ($tippin) {
            $this->assertSame($tippin->getKey(), $event->provider->getKey());
            $this->assertSame($this->group->id, $event->thread->id);
            $this->assertSame($this->participant->id, $event->participant->id);

            return true;
        });
    }
}
