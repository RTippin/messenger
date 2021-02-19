<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Invites\JoinWithInvite;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\InviteUsedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Listeners\JoinedWithInviteMessage;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\Definitions;
use RTippin\Messenger\Tests\FeatureTestCase;

class JoinWithInviteTest extends FeatureTestCase
{
    private Thread $group;

    private Invite $invite;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->group = $this->createGroupThread($tippin);

        $this->invite = $this->group->invites()
            ->create([
                'owner_id' => $tippin->getKey(),
                'owner_type' => get_class($tippin),
                'code' => 'TEST1234',
                'max_use' => 10,
                'uses' => 2,
                'expires_at' => now()->addHour(),
            ]);

        Messenger::setProvider($this->doe);
    }

    /** @test */
    public function join_with_invite_throws_exception_when_invites_disabled()
    {
        Messenger::setThreadInvites(false);

        $this->expectException(FeatureDisabledException::class);

        $this->expectExceptionMessage('Group invites are currently disabled.');

        app(JoinWithInvite::class)->withoutDispatches()->execute($this->invite);
    }

    /** @test */
    public function join_with_invite_stores_fresh_participant()
    {
        app(JoinWithInvite::class)->withoutDispatches()->execute($this->invite);

        $this->assertDatabaseHas('participants', [
            'thread_id' => $this->group->id,
            'owner_id' => $this->doe->getKey(),
            'owner_type' => get_class($this->doe),
            'admin' => false,
        ]);
    }

    /** @test */
    public function join_with_invite_increments_uses()
    {
        app(JoinWithInvite::class)->withoutDispatches()->execute($this->invite);

        $this->assertDatabaseHas('thread_invites', [
            'id' => $this->invite->id,
            'uses' => 3,
        ]);
    }

    /** @test */
    public function join_with_invite_restores_soft_deleted_participant()
    {
        $participant = $this->group->participants()
            ->create(array_merge(Definitions::DefaultAdminParticipant, [
                'owner_id' => $this->doe->getKey(),
                'owner_type' => get_class($this->doe),
                'deleted_at' => now(),
            ]));

        app(JoinWithInvite::class)->withoutDispatches()->execute($this->invite);

        $this->assertDatabaseCount('participants', 2);

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'deleted_at' => null,
            'admin' => false,
        ]);
    }

    /** @test */
    public function join_with_invite_fires_event()
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
    public function join_with_invite_triggers_listener()
    {
        Bus::fake();

        app(JoinWithInvite::class)->execute($this->invite);

        Bus::assertDispatched(function (CallQueuedListener $job) {
            return $job->class === JoinedWithInviteMessage::class;
        });
    }
}
