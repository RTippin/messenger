<?php

namespace RTippin\Messenger\Tests\Commands;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\Bus;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\ArchiveInvalidInvites;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class InvitesCheckCommandTest extends FeatureTestCase
{
    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->createGroupThread($this->tippin);
        Bus::fake();
    }

    /** @test */
    public function it_does_nothing_if_no_invalid_invites_found()
    {
        Invite::factory()->for($this->group)->owner($this->tippin)->create();

        $this->artisan('messenger:invites:check-valid')
            ->expectsOutput('No invalid invites found.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(ArchiveInvalidInvites::class);
    }

    /** @test */
    public function it_does_nothing_if_invite_not_expired()
    {
        Invite::factory()
            ->for($this->group)
            ->owner($this->tippin)
            ->expires(now()->addMinutes(10))
            ->create();

        $this->artisan('messenger:invites:check-valid')
            ->expectsOutput('No invalid invites found.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(ArchiveInvalidInvites::class);
    }

    /** @test */
    public function it_does_nothing_when_invites_disabled()
    {
        Messenger::setThreadInvites(false);

        $this->artisan('messenger:invites:check-valid')
            ->expectsOutput('Thread invites are currently disabled.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(ArchiveInvalidInvites::class);
    }

    /** @test */
    public function it_runs_job_now()
    {
        Invite::factory()
            ->for($this->group)
            ->owner($this->tippin)
            ->expires(now())
            ->create();

        $this->artisan('messenger:invites:check-valid', [
            '--now' => true,
        ])
            ->expectsOutput('1 invalid invites found. Archive invites completed!')
            ->assertExitCode(0);

        Bus::assertDispatched(ArchiveInvalidInvites::class);
    }

    /** @test */
    public function it_dispatches_job_if_invite_has_max_use()
    {
        Invite::factory()
            ->for($this->group)
            ->owner($this->tippin)
            ->create([
                'max_use' => 1,
                'uses' => 1,
            ]);

        $this->artisan('messenger:invites:check-valid')
            ->expectsOutput('1 invalid invites found. Archive invites dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(ArchiveInvalidInvites::class);
    }

    /** @test */
    public function it_dispatches_job_if_invite_has_expired()
    {
        Invite::factory()
            ->for($this->group)
            ->owner($this->tippin)
            ->expires(now()->addMinutes(5))
            ->create();
        $this->travel(10)->minutes();

        $this->artisan('messenger:invites:check-valid')
            ->expectsOutput('1 invalid invites found. Archive invites dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(ArchiveInvalidInvites::class);
    }

    /** @test */
    public function it_finds_multiple_invalid_invites()
    {
        Invite::factory()
            ->for($this->group)
            ->owner($this->tippin)
            ->state(new Sequence(
                [
                    'max_use' => 1,
                    'uses' => 1,
                ],
                [
                    'expires_at' => now()->addMinutes(5),
                ],
            ))
            ->count(2)
            ->create();

        $this->travel(10)->minutes();

        $this->artisan('messenger:invites:check-valid')
            ->expectsOutput('2 invalid invites found. Archive invites dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(ArchiveInvalidInvites::class);
    }
}
