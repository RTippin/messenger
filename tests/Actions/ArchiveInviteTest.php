<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Invites\ArchiveInvite;
use RTippin\Messenger\Events\InviteArchivedEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class ArchiveInviteTest extends FeatureTestCase
{
    /** @test */
    public function it_throws_exception_if_disabled()
    {
        Messenger::setThreadInvites(false);
        $invite = Invite::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Group invites are currently disabled.');

        app(ArchiveInvite::class)->execute($invite);
    }

    /** @test */
    public function it_soft_deletes_invite()
    {
        $invite = Invite::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        app(ArchiveInvite::class)->execute($invite);

        $this->assertSoftDeleted('thread_invites', [
            'id' => $invite->id,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            InviteArchivedEvent::class,
        ]);
        $invite = Invite::factory()->for(Thread::factory()->group()->create())->owner($this->tippin)->create();

        app(ArchiveInvite::class)->execute($invite);

        Event::assertDispatched(function (InviteArchivedEvent $event) use ($invite) {
            return $invite->id === $event->invite->id;
        });
    }
}
