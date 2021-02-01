<?php

namespace RTippin\Messenger\Tests\Commands;

use Illuminate\Support\Facades\Bus;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\CheckCallsActivity;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallActivityCheckTest extends FeatureTestCase
{
    private MessengerProvider $tippin;

    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->group = $this->createGroupThread($this->tippin);
    }

    /** @test */
    public function checker_command_does_nothing_with_no_active_calls()
    {
        $this->artisan('messenger:calls:check-activity')
            ->expectsOutput('No active calls.')
            ->assertExitCode(0);
    }

    /** @test */
    public function checker_command_does_nothing_when_calling_disabled_in_config()
    {
        Messenger::setCalling(false);

        $this->artisan('messenger:calls:check-activity')
            ->expectsOutput('Call system currently disabled.')
            ->assertExitCode(0);
    }

    /** @test */
    public function checker_command_ignores_calls_created_within_the_last_minute()
    {
        $this->createCall($this->group, $this->tippin);

        Bus::fake();

        $this->artisan('messenger:calls:check-activity')
            ->expectsOutput('Activity checks dispatched!')
            ->assertExitCode(0);

        Bus::assertNotDispatched(CheckCallsActivity::class);
    }

    /** @test */
    public function checker_command_dispatches_checker_job()
    {
        $this->createCall($this->group, $this->tippin);

        Bus::fake();

        $this->travel(2)->minutes();

        $this->artisan('messenger:calls:check-activity')
            ->expectsOutput('Activity checks dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(CheckCallsActivity::class);
    }

    /** @test */
    public function checker_command_runs_checker_job_now()
    {
        $this->createCall($this->group, $this->tippin);

        Bus::fake();

        $this->travel(2)->minutes();

        $this->artisan('messenger:calls:check-activity', [
            '--now' => true,
        ])
            ->expectsOutput('Activity checks completed!')
            ->assertExitCode(0);

        Bus::assertDispatched(CheckCallsActivity::class);
    }
}
