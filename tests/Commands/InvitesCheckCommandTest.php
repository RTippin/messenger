<?php

namespace RTippin\Messenger\Tests\Commands;

use Illuminate\Support\Facades\Bus;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\ArchiveInvalidInvites;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class InvitesCheckCommandTest extends FeatureTestCase
{
    private MessengerProvider $tippin;
    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->group = $this->createGroupThread($this->tippin);
        Bus::fake();
    }

    /** @test */
    public function it_does_nothing_if_no_invalid_invites_found()
    {
        $this->group->invites()->create([
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'code' => 'TEST1234',
            'max_use' => 1,
            'uses' => 0,
            'expires_at' => null,
        ]);

        $this->artisan('messenger:invites:check-valid')
            ->expectsOutput('No invalid invites found.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(ArchiveInvalidInvites::class);
    }

    /** @test */
    public function it_does_nothing_if_invite_not_expired()
    {
        $this->group->invites()->create([
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'code' => 'TEST1234',
            'max_use' => 1,
            'uses' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

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
        $this->group->invites()->create([
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'code' => 'TEST1234',
            'max_use' => 1,
            'uses' => 1,
            'expires_at' => now(),
        ]);

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
        $this->group->invites()->create([
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'code' => 'TEST1234',
            'max_use' => 1,
            'uses' => 1,
            'expires_at' => null,
        ]);

        $this->artisan('messenger:invites:check-valid')
            ->expectsOutput('1 invalid invites found. Archive invites dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(ArchiveInvalidInvites::class);
    }

    /** @test */
    public function it_dispatches_job_if_invite_has_expired()
    {
        $this->group->invites()->create([
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'code' => 'TEST1234',
            'max_use' => 1,
            'uses' => 0,
            'expires_at' => now()->addMinutes(5),
        ]);
        $this->travel(10)->minutes();

        $this->artisan('messenger:invites:check-valid')
            ->expectsOutput('1 invalid invites found. Archive invites dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(ArchiveInvalidInvites::class);
    }

    /** @test */
    public function it_finds_multiple_invalid_invites()
    {
        $this->group->invites()->create([
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'code' => 'TEST1234',
            'max_use' => 1,
            'uses' => 1,
            'expires_at' => null,
        ]);

        $this->group->invites()->create([
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'code' => 'TEST5678',
            'max_use' => 0,
            'uses' => 0,
            'expires_at' => now()->addMinutes(5),
        ]);
        $this->travel(10)->minutes();

        $this->artisan('messenger:invites:check-valid')
            ->expectsOutput('2 invalid invites found. Archive invites dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(ArchiveInvalidInvites::class);
    }
}
