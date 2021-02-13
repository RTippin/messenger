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
    private MessengerProvider $tippin;

    private Thread $group;

    private Invite $invite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->group = $this->createGroupThread($this->tippin);

        $this->invite = $this->group->invites()->create([
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'code' => 'TEST1234',
            'max_use' => 10,
            'uses' => 1,
            'expires_at' => now()->addMinutes(5),
        ]);
    }

    /** @test */
    public function invite_exists()
    {
        $this->assertDatabaseCount('thread_invites', 1);
        $this->assertDatabaseHas('thread_invites', [
            'id' => $this->invite->id,
        ]);
        $this->assertInstanceOf(Invite::class, $this->invite);
    }

    /** @test */
    public function invite_attributes_casted()
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
    public function invite_has_relations()
    {
        $this->assertSame($this->tippin->getKey(), $this->invite->owner->getKey());
        $this->assertSame($this->group->id, $this->invite->thread->id);
        $this->assertInstanceOf(Thread::class, $this->invite->thread);
        $this->assertInstanceOf(MessengerProvider::class, $this->invite->owner);
    }

    /** @test */
    public function invite_owner_returns_ghost_when_not_found()
    {
        $this->invite->update([
            'owner_id' => 404,
        ]);

        $this->assertInstanceOf(GhostUser::class, $this->invite->owner);
    }

    /** @test */
    public function invite_has_route()
    {
        $this->assertStringContainsString('/messenger/join/TEST1234', $this->invite->getInvitationRoute());
    }

    /** @test */
    public function invite_is_valid()
    {
        $this->assertTrue($this->invite->isValid());
        $this->assertSame(1, Invite::valid()->count());
    }

    /** @test */
    public function invite_valid_when_uses_unlimited()
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
    public function invite_valid_when_never_expired()
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
    public function invite_invalid_when_past_expiration()
    {
        $this->travel(1)->hours();

        $this->assertFalse($this->invite->isValid());
        $this->assertSame(0, Invite::valid()->count());
        $this->assertSame(1, Invite::invalid()->count());
    }

    /** @test */
    public function invite_invalid_when_thread_removed()
    {
        $this->group->delete();

        $this->assertFalse($this->invite->isValid());
    }

    /** @test */
    public function invite_invalid_when_thread_invitations_disabled()
    {
        $this->group->update([
            'invitations' => false,
        ]);

        $this->assertFalse($this->invite->isValid());
    }

    /** @test */
    public function invite_invalid_when_thread_locked()
    {
        $this->group->update([
            'lockout' => true,
        ]);

        $this->assertFalse($this->invite->isValid());
    }

    /** @test */
    public function invite_invalid_when_uses_equal_max_use()
    {
        $this->invite->update([
            'uses' => 10,
        ]);

        $this->assertFalse($this->invite->isValid());
        $this->assertSame(0, Invite::valid()->count());
        $this->assertSame(1, Invite::invalid()->count());
    }

    /** @test */
    public function invite_invalid_when_uses_greater_than_max_use()
    {
        $this->invite->update([
            'uses' => 15,
        ]);

        $this->assertFalse($this->invite->isValid());
        $this->assertSame(0, Invite::valid()->count());
        $this->assertSame(1, Invite::invalid()->count());
    }
}
