<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class InviteTest extends FeatureTestCase
{
    /** @test */
    public function it_exists()
    {
        $invite = Invite::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->create();

        $this->assertDatabaseCount('thread_invites', 1);
        $this->assertDatabaseHas('thread_invites', [
            'id' => $invite->id,
        ]);
        $this->assertInstanceOf(Invite::class, $invite);
    }

    /** @test */
    public function it_cast_attributes()
    {
        $invite = Invite::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->trashed()->create([
            'expires_at' => now(),
            'max_use' => 10,
            'uses' => 1,
        ]);

        $this->assertInstanceOf(Carbon::class, $invite->created_at);
        $this->assertInstanceOf(Carbon::class, $invite->updated_at);
        $this->assertInstanceOf(Carbon::class, $invite->expires_at);
        $this->assertInstanceOf(Carbon::class, $invite->deleted_at);
        $this->assertIsInt($invite->max_use);
        $this->assertIsInt($invite->uses);
    }

    /** @test */
    public function it_has_relations()
    {
        $thread = Thread::factory()->group()->create();
        $invite = Invite::factory()->for($thread)->owner($this->tippin)->create();
        $this->assertSame($this->tippin->getKey(), $invite->owner->getKey());
        $this->assertSame($thread->id, $invite->thread->id);
        $this->assertInstanceOf(Thread::class, $invite->thread);
        $this->assertInstanceOf(MessengerProvider::class, $invite->owner);
    }

    /** @test */
    public function owner_returns_ghost_if_not_found()
    {
        $invite = Invite::factory()->for(
            Thread::factory()->group()->create()
        )->create([
            'owner_id' => 404,
            'owner_type' => $this->tippin->getMorphClass(),
        ]);

        $this->assertInstanceOf(GhostUser::class, $invite->owner);
    }

    /** @test */
    public function it_is_owned_by_current_provider()
    {
        Messenger::setProvider($this->tippin);
        $invite = Invite::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->create();

        $this->assertTrue($invite->isOwnedByCurrentProvider());
    }

    /** @test */
    public function it_is_not_owned_by_current_provider()
    {
        Messenger::setProvider($this->doe);
        $invite = Invite::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->create();

        $this->assertFalse($invite->isOwnedByCurrentProvider());
    }

    /** @test */
    public function it_has_private_owner_channel()
    {
        $invite = Invite::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->create();

        $this->assertSame('user.'.$this->tippin->getKey(), $invite->getOwnerPrivateChannel());
    }

    /** @test */
    public function it_does_not_have_route()
    {
        $invite = Invite::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->testing()->create();

        $this->assertNull($invite->getInvitationRoute());
    }

    /** @test */
    public function it_has_avatar_routes()
    {
        $invite = Invite::factory()->for(
            Thread::factory()->group()->create(['image' => '5.png'])
        )->owner($this->tippin)->testing()->create();
        $inviteAvatar = [
            'sm' => '/messenger/assets/invites/TEST1234/avatar/sm/5.png',
            'md' => '/messenger/assets/invites/TEST1234/avatar/md/5.png',
            'lg' => '/messenger/assets/invites/TEST1234/avatar/lg/5.png',
        ];

        $this->assertSame($inviteAvatar, $invite->inviteAvatar());
    }

    /** @test */
    public function it_is_valid()
    {
        $invite = Invite::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->create();

        $this->assertTrue($invite->isValid());
        $this->assertSame(1, Invite::valid()->count());
    }

    /** @test */
    public function it_is_valid_if_uses_unlimited()
    {
        $invite = Invite::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->create([
            'uses' => 10000,
            'max_use' => 0,
        ]);

        $this->assertTrue($invite->isValid());
        $this->assertSame(1, Invite::valid()->count());
        $this->assertSame(0, Invite::invalid()->count());
    }

    /** @test */
    public function it_is_valid_if_never_expired()
    {
        $invite = Invite::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->create();
        $this->travel(5)->years();

        $this->assertTrue($invite->isValid());
        $this->assertSame(1, Invite::valid()->count());
        $this->assertSame(0, Invite::invalid()->count());
    }

    /** @test */
    public function it_is_invalid_if_past_expiration()
    {
        $invite = Invite::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->expires(now()->addMinutes(10))->create();
        $this->travel(1)->hours();

        $this->assertFalse($invite->isValid());
        $this->assertSame(0, Invite::valid()->count());
        $this->assertSame(1, Invite::invalid()->count());
    }

    /** @test */
    public function it_is_invalid_if_thread_removed()
    {
        $invite = Invite::factory()->for(
            Thread::factory()->group()->trashed()->create()
        )->owner($this->tippin)->create();

        $this->assertFalse($invite->isValid());
    }

    /** @test */
    public function it_is_invalid_if_thread_invitations_disabled()
    {
        $invite = Invite::factory()->for(
            Thread::factory()->group()->create(['invitations' => false])
        )->owner($this->tippin)->create();

        $this->assertFalse($invite->isValid());
    }

    /** @test */
    public function it_is_invalid_if_thread_locked()
    {
        $invite = Invite::factory()->for(
            Thread::factory()->group()->locked()->create()
        )->owner($this->tippin)->create();

        $this->assertFalse($invite->isValid());
    }

    /** @test */
    public function it_is_invalid_if_uses_equal_max_use()
    {
        $invite = Invite::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->create([
            'uses' => 10,
            'max_use' => 10,
        ]);

        $this->assertFalse($invite->isValid());
        $this->assertSame(0, Invite::valid()->count());
        $this->assertSame(1, Invite::invalid()->count());
    }

    /** @test */
    public function it_is_invalid_if_uses_greater_than_max_use()
    {
        $invite = Invite::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->create([
            'uses' => 15,
            'max_use' => 10,
        ]);

        $this->assertFalse($invite->isValid());
        $this->assertSame(0, Invite::valid()->count());
        $this->assertSame(1, Invite::invalid()->count());
    }
}
