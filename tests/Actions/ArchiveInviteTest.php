<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Invites\ArchiveInvite;
use RTippin\Messenger\Events\InviteArchivedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Tests\FeatureTestCase;

class ArchiveInviteTest extends FeatureTestCase
{
    private Invite $invite;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $group = $this->createGroupThread($tippin);

        $this->invite = $group->invites()->create([
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
            'code' => 'TEST1234',
            'max_use' => 1,
            'uses' => 0,
            'expires_at' => null,
        ]);
    }

    /** @test */
    public function archive_invite_throws_exception_if_disabled()
    {
        Messenger::setThreadInvites(false);

        $this->expectException(FeatureDisabledException::class);

        $this->expectExceptionMessage('Group invites are currently disabled.');

        app(ArchiveInvite::class)->withoutDispatches()->execute($this->invite);
    }

    /** @test */
    public function archive_invite_soft_deletes_invite()
    {
        app(ArchiveInvite::class)->withoutDispatches()->execute($this->invite);

        $this->assertSoftDeleted('thread_invites', [
            'id' => $this->invite->id,
        ]);
    }

    /** @test */
    public function archive_invite_fires_event()
    {
        Event::fake([
            InviteArchivedEvent::class,
        ]);

        app(ArchiveInvite::class)->execute($this->invite);

        Event::assertDispatched(function (InviteArchivedEvent $event) {
            return $this->invite->id === $event->invite->id;
        });
    }
}
