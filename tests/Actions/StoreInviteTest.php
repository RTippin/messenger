<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Invites\StoreInvite;
use RTippin\Messenger\Events\NewInviteEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreInviteTest extends FeatureTestCase
{
    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $this->group = $this->createGroupThread($tippin);

        Messenger::setProvider($tippin);
    }

    /** @test */
    public function store_invite_throws_exception_if_disabled()
    {
        Messenger::setThreadInvites(false);

        $this->expectException(FeatureDisabledException::class);

        $this->expectExceptionMessage('Group invites are currently disabled.');

        app(StoreInvite::class)->withoutDispatches()->execute(
            $this->group,
            [
                'expires' => 0,
                'uses' => 0,
            ]
        );
    }

    /** @test */
    public function store_invite_stores_invite()
    {
        app(StoreInvite::class)->withoutDispatches()->execute(
            $this->group,
            [
                'expires' => 0,
                'uses' => 0,
            ]
        );

        $this->assertDatabaseHas('thread_invites', [
            'thread_id' => $this->group->id,
            'max_use' => 0,
            'expires_at' => null,
        ]);
    }

    /** @test */
    public function store_invite_fires_event()
    {
        Event::fake([
            NewInviteEvent::class,
        ]);

        app(StoreInvite::class)->execute(
            $this->group,
            [
                'expires' => 0,
                'uses' => 0,
            ]
        );

        Event::assertDispatched(function (NewInviteEvent $event) {
            return $this->group->id === $event->invite->thread_id;
        });
    }
}
