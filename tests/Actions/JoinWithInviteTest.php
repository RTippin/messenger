<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Invites\JoinWithInvite;
use RTippin\Messenger\Events\InviteUsedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Listeners\JoinedWithInviteMessage;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class JoinWithInviteTest extends FeatureTestCase
{
    private Thread $group;
    private Invite $invite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->createGroupThread($this->tippin);
        $this->invite = Invite::factory()->for($this->group)->owner($this->tippin)->create(['uses' => 2]);
        Messenger::setProvider($this->doe);
    }

    /** @test */
    public function it_throws_exception_if_invites_disabled()
    {
        Messenger::setThreadInvites(false);

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Group invites are currently disabled.');

        app(JoinWithInvite::class)->withoutDispatches()->execute($this->invite);
    }

    /** @test */
    public function it_stores_participant()
    {
        app(JoinWithInvite::class)->withoutDispatches()->execute($this->invite);

        $this->assertDatabaseHas('participants', [
            'thread_id' => $this->group->id,
            'owner_id' => $this->doe->getKey(),
            'owner_type' => $this->doe->getMorphClass(),
            'admin' => false,
        ]);
    }

    /** @test */
    public function it_increments_invite_uses()
    {
        app(JoinWithInvite::class)->withoutDispatches()->execute($this->invite);

        $this->assertDatabaseHas('thread_invites', [
            'id' => $this->invite->id,
            'uses' => 3,
        ]);
    }

    /** @test */
    public function it_restores_soft_deleted_participant()
    {
        $participant = Participant::factory()->for($this->group)->owner($this->doe)->create(['deleted_at' => now()]);

        app(JoinWithInvite::class)->withoutDispatches()->execute($this->invite);

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
        Event::fake([
            InviteUsedEvent::class,
        ]);

        app(JoinWithInvite::class)->execute($this->invite);

        Event::assertDispatched(function (InviteUsedEvent $event) {
            $this->assertSame($this->doe->getKey(), $event->provider->getKey());
            $this->assertSame($this->group->id, $event->thread->id);
            $this->assertSame($this->invite->id, $event->invite->id);

            return true;
        });
    }

    /** @test */
    public function it_dispatches_listeners()
    {
        Bus::fake();

        app(JoinWithInvite::class)->execute($this->invite);

        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === JoinedWithInviteMessage::class;
        });
    }
}
