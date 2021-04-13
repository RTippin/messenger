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

        $this->group = $this->createGroupThread($this->tippin);
        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_throws_exception_if_disabled()
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
    public function it_stores_invite()
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
    public function it_fires_events()
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
