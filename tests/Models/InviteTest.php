<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class InviteTest extends FeatureTestCase
{
    private Thread $group;
    private Invite $invite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->createGroupThread($this->tippin);
        $this->invite = Invite::factory()
            ->for($this->group)
            ->owner($this->tippin)
            ->expires(now()->addMinutes(5))
            ->testing()
            ->create([
                'max_use' => 10,
                'uses' => 1,
            ]);
    }

    /** @test */
    public function it_exists()
    {
        $this->assertDatabaseCount('thread_invites', 1);
        $this->assertDatabaseHas('thread_invites', [
            'id' => $this->invite->id,
        ]);
        $this->assertInstanceOf(Invite::class, $this->invite);
    }

    /** @test */
    public function it_cast_attributes()
    {
        $this->invite->delete();

        $this->assertInstanceOf(Carbon::class, $this->invite->created_at);
        $this->assertInstanceOf(Carbon::class, $this->invite->updated_at);
        $this->assertInstanceOf(Carbon::class, $this->invite->expires_at);
        $this->assertInstanceOf(Carbon::class, $this->invite->deleted_at);
        $this->assertSame(10, $this->invite->max_use);
        $this->assertSame(1, $this->invite->uses);
    }

    /** @test */
    public function it_has_relations()
    {
        $this->assertSame($this->tippin->getKey(), $this->invite->owner->getKey());
        $this->assertSame($this->group->id, $this->invite->thread->id);
        $this->assertInstanceOf(Thread::class, $this->invite->thread);
        $this->assertInstanceOf(MessengerProvider::class, $this->invite->owner);
    }

    /** @test */
    public function owner_returns_ghost_if_not_found()
    {
        $this->invite->update([
            'owner_id' => 404,
        ]);

        $this->assertInstanceOf(GhostUser::class, $this->invite->owner);
    }

    /** @test */
    public function it_has_route()
    {
        $this->assertStringContainsString('/messenger/join/TEST1234', $this->invite->getInvitationRoute());
    }

    /** @test */
    public function it_is_valid()
    {
        $this->assertTrue($this->invite->isValid());
        $this->assertSame(1, Invite::valid()->count());
    }

    /** @test */
    public function it_is_valid_if_uses_unlimited()
    {
        $this->invite->update([
            'uses' => 10000,
            'max_use' => 0,
        ]);

        $this->assertTrue($this->invite->isValid());
        $this->assertSame(1, Invite::valid()->count());
        $this->assertSame(0, Invite::invalid()->count());
    }

    /** @test */
    public function it_is_valid_if_never_expired()
    {
        $this->invite->update([
            'expires_at' => null,
        ]);
        $this->travel(5)->years();

        $this->assertTrue($this->invite->isValid());
        $this->assertSame(1, Invite::valid()->count());
        $this->assertSame(0, Invite::invalid()->count());
    }

    /** @test */
    public function it_is_invalid_if_past_expiration()
    {
        $this->travel(1)->hours();

        $this->assertFalse($this->invite->isValid());
        $this->assertSame(0, Invite::valid()->count());
        $this->assertSame(1, Invite::invalid()->count());
    }

    /** @test */
    public function it_is_invalid_if_thread_removed()
    {
        $this->group->delete();

        $this->assertFalse($this->invite->isValid());
    }

    /** @test */
    public function it_is_invalid_if_thread_invitations_disabled()
    {
        $this->group->update([
            'invitations' => false,
        ]);

        $this->assertFalse($this->invite->isValid());
    }

    /** @test */
    public function it_is_invalid_if_thread_locked()
    {
        $this->group->update([
            'lockout' => true,
        ]);

        $this->assertFalse($this->invite->isValid());
    }

    /** @test */
    public function it_is_invalid_if_uses_equal_max_use()
    {
        $this->invite->update([
            'uses' => 10,
        ]);

        $this->assertFalse($this->invite->isValid());
        $this->assertSame(0, Invite::valid()->count());
        $this->assertSame(1, Invite::invalid()->count());
    }

    /** @test */
    public function it_is_invalid_if_uses_greater_than_max_use()
    {
        $this->invite->update([
            'uses' => 15,
        ]);

        $this->assertFalse($this->invite->isValid());
        $this->assertSame(0, Invite::valid()->count());
        $this->assertSame(1, Invite::invalid()->count());
    }
}
