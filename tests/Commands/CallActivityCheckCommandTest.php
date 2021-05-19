<?php

namespace RTippin\Messenger\Tests\Commands;

use Illuminate\Support\Facades\Bus;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\CheckCallsActivity;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallActivityCheckCommandTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    /** @test */
    public function it_does_nothing_if_no_active_calls_exist()
    {
        $this->artisan('messenger:calls:check-activity')
            ->expectsOutput('No matching active calls found.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(CheckCallsActivity::class);
    }

    /** @test */
    public function it_does_nothing_if_calling_disabled()
    {
        Messenger::setCalling(false);

        $this->artisan('messenger:calls:check-activity')
            ->expectsOutput('Call system currently disabled.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(CheckCallsActivity::class);
    }

    /** @test */
    public function it_ignores_calls_created_within_the_last_minute()
    {
        Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->create();

        $this->artisan('messenger:calls:check-activity')
            ->expectsOutput('No matching active calls found.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(CheckCallsActivity::class);
    }

    /** @test */
    public function it_dispatches_job()
    {
        Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->create();
        $this->travel(2)->minutes();

        $this->artisan('messenger:calls:check-activity')
            ->expectsOutput('1 active calls found. Call activity checks dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(CheckCallsActivity::class);
    }

    /** @test */
    public function it_runs_job_now()
    {
        Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->create();
        $this->travel(2)->minutes();

        $this->artisan('messenger:calls:check-activity', [
            '--now' => true,
        ])
            ->expectsOutput('1 active calls found. Call activity checks completed!')
            ->assertExitCode(0);

        Bus::assertDispatchedSync(CheckCallsActivity::class);
    }

    /** @test */
    public function it_dispatches_multiple_jobs_chunking_per_100()
    {
        $thread = Thread::factory()->create();
        Call::factory()->for($thread)->owner($this->tippin)->setup()->count(200)->create();
        $this->travel(2)->minutes();

        $this->artisan('messenger:calls:check-activity')
            ->expectsOutput('200 active calls found. Call activity checks dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatchedTimes(CheckCallsActivity::class, 2);
    }
}
