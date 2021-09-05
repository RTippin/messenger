<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Invites\StoreInvite;
use RTippin\Messenger\Events\NewInviteEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreInviteTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_throws_exception_if_disabled()
    {
        Messenger::setThreadInvites(false);

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Group invites are currently disabled.');

        app(StoreInvite::class)->execute(Thread::factory()->group()->create(), [
            'uses' => 0,
        ]);
    }

    /** @test */
    public function it_stores_invite_without_expiration()
    {
        $thread = Thread::factory()->group()->create();

        app(StoreInvite::class)->execute($thread, [
            'uses' => 0,
        ]);

        $this->assertDatabaseHas('thread_invites', [
            'thread_id' => $thread->id,
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'max_use' => 0,
            'expires_at' => null,
        ]);
        $this->assertSame(10, mb_strlen(Invite::first()->code));
    }

    /** @test */
    public function it_stores_invite_with_expiration()
    {
        $thread = Thread::factory()->group()->create();
        $expires = now()->addHour();

        app(StoreInvite::class)->execute($thread, [
            'expires' => $expires,
            'uses' => 0,
        ]);

        $this->assertDatabaseHas('thread_invites', [
            'thread_id' => $thread->id,
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => $this->tippin->getMorphClass(),
            'max_use' => 0,
            'expires_at' => $expires,
        ]);
        $this->assertSame(10, mb_strlen(Invite::first()->code));
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewInviteEvent::class,
        ]);
        $thread = Thread::factory()->group()->create();

        app(StoreInvite::class)->execute($thread, [
            'expires' => 0,
            'uses' => 0,
        ]);

        Event::assertDispatched(function (NewInviteEvent $event) use ($thread) {
            return $thread->id === $event->invite->thread_id;
        });
    }
}
