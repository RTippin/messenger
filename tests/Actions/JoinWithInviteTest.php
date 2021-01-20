<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Invites\JoinWithInvite;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Events\InviteUsedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Thread;
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
                'max_use' => 1,
                'uses' => 0,
                'expires_at' => now()->addHour(),
            ]);

        Messenger::setProvider($this->doe);
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
            'uses' => 1,
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
}
