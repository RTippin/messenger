<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Invites\JoinWithInvite;
use RTippin\Messenger\Events\InviteUsedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\JoinedWithInviteMessage;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class JoinWithInviteTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_throws_exception_if_invites_disabled()
    {
        Messenger::setThreadInvites(false);
        $invite = Invite::factory()->for(Thread::factory()->group()->create())->owner($this->doe)->create();

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Group invites are currently disabled.');

        app(JoinWithInvite::class)->execute($invite);
    }

    /** @test */
    public function it_stores_participant()
    {
        $thread = Thread::factory()->group()->create();
        $invite = Invite::factory()->for($thread)->owner($this->doe)->create();

        app(JoinWithInvite::class)->execute($invite);

        $this->assertDatabaseHas('participants', [
            'thread_id' => $thread->id,
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'admin' => false,
        ]);
    }

    /** @test */
    public function it_increments_invite_uses()
    {
        $invite = Invite::factory()->for(Thread::factory()->group()->create())->owner($this->doe)->create(['uses' => 3]);

        app(JoinWithInvite::class)->execute($invite);

        $this->assertDatabaseHas('thread_invites', [
            'id' => $invite->id,
            'uses' => 4,
        ]);
    }

    /** @test */
    public function it_restores_soft_deleted_participant()
    {
        $thread = $this->createGroupThread($this->doe);
        $invite = Invite::factory()->for($thread)->owner($this->doe)->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->trashed()->create();

        app(JoinWithInvite::class)->execute($invite);

        $this->assertDatabaseCount('participants', 2);
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'deleted_at' => null,
            'admin' => false,
        ]);
    }

    /** @test */
    public function it_restores_soft_deleted_admin_to_default_permissions()
    {
        $thread = $this->createGroupThread($this->doe);
        $invite = Invite::factory()->for($thread)->owner($this->doe)->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->admin()->trashed()->create();

        app(JoinWithInvite::class)->execute($invite);

        $this->assertDatabaseCount('participants', 2);
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'deleted_at' => null,
            'admin' => false,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            InviteUsedEvent::class,
        ]);
        $thread = Thread::factory()->group()->create();
        $invite = Invite::factory()->for($thread)->owner($this->doe)->create();

        app(JoinWithInvite::class)->execute($invite);

        Event::assertDispatched(function (InviteUsedEvent $event) use ($thread, $invite) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($thread->id, $event->thread->id);
            $this->assertSame($invite->id, $event->invite->id);

            return true;
        });
    }

    /** @test */
    public function it_dispatches_subscriber_job()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        $invite = Invite::factory()->for(Thread::factory()->group()->create())->owner($this->doe)->create();

        app(JoinWithInvite::class)->execute($invite);

        Bus::assertDispatched(JoinedWithInviteMessage::class);
    }

    /** @test */
    public function it_runs_subscriber_job_now()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('queued', false);
        $invite = Invite::factory()->for(Thread::factory()->group()->create())->owner($this->doe)->create();

        app(JoinWithInvite::class)->execute($invite);

        Bus::assertDispatchedSync(JoinedWithInviteMessage::class);
    }

    /** @test */
    public function it_doesnt_dispatch_subscriber_job_if_disabled()
    {
        BaseMessengerAction::enableEvents();
        Bus::fake();
        Messenger::setSystemMessageSubscriber('enabled', false);
        $invite = Invite::factory()->for(Thread::factory()->group()->create())->owner($this->doe)->create();

        app(JoinWithInvite::class)->execute($invite);

        Bus::assertNotDispatched(JoinedWithInviteMessage::class);
    }
}
