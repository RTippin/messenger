<?php

namespace RTippin\Messenger\Tests\Commands;

use Illuminate\Support\Facades\Bus;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\CheckCallsActivity;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallActivityCheckCommandTest extends FeatureTestCase
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
    public function call_command_does_nothing_when_no_active_calls_found()
    {
        $this->artisan('messenger:calls:check-activity')
            ->expectsOutput('No matching active calls found.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(CheckCallsActivity::class);
    }

    /** @test */
    public function call_command_does_nothing_when_calling_disabled_in_config()
    {
        Messenger::setCalling(false);

        $this->artisan('messenger:calls:check-activity')
            ->expectsOutput('Call system currently disabled.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(CheckCallsActivity::class);
    }

    /** @test */
    public function call_command_ignores_calls_created_within_the_last_minute()
    {
        $this->createCall($this->group, $this->tippin);

        $this->artisan('messenger:calls:check-activity')
            ->expectsOutput('No matching active calls found.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(CheckCallsActivity::class);
    }

    /** @test */
    public function call_command_dispatches_job()
    {
        $this->createCall($this->group, $this->tippin);

        $this->travel(2)->minutes();

        $this->artisan('messenger:calls:check-activity')
            ->expectsOutput('1 active calls found. Call activity checks dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(CheckCallsActivity::class);
    }

    /** @test */
    public function call_command_runs_job_now()
    {
        $this->createCall($this->group, $this->tippin);

        $this->travel(2)->minutes();

        $this->artisan('messenger:calls:check-activity', [
            '--now' => true,
        ])
            ->expectsOutput('1 active calls found. Call activity checks completed!')
            ->assertExitCode(0);

        Bus::assertDispatched(CheckCallsActivity::class);
    }

    /** @test */
    public function call_command_finds_multiple_calls()
    {
        $this->createCall($this->group, $this->tippin);

        $this->createCall($this->group, $this->tippin);

        $this->travel(2)->minutes();

        $this->artisan('messenger:calls:check-activity')
            ->expectsOutput('2 active calls found. Call activity checks dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(CheckCallsActivity::class);
    }
}
