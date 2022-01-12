<?php

namespace RTippin\Messenger\Tests\Commands;

use Illuminate\Support\Facades\Bus;
use RTippin\Messenger\Jobs\PurgeBots;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PurgeBotsCommandTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    /** @test */
    public function it_doesnt_find_bots()
    {
        $this->artisan('messenger:purge:bots')
            ->expectsOutput('No bots archived 30 days or greater found.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(PurgeBots::class);
    }

    /** @test */
    public function it_can_set_days()
    {
        $this->artisan('messenger:purge:bots', [
            '--days' => 10,
        ])
            ->expectsOutput('No bots archived 10 days or greater found.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(PurgeBots::class);
    }

    /** @test */
    public function it_dispatches_job()
    {
        Bot::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->trashed(now()->subMonths(2))->create();

        $this->artisan('messenger:purge:bots')
            ->expectsOutput('1 bots archived 30 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeBots::class);
    }

    /** @test */
    public function it_runs_job_now()
    {
        Bot::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->trashed(now()->subMonths(2))->create();

        $this->artisan('messenger:purge:bots', [
            '--now' => true,
        ])
            ->expectsOutput('1 bots archived 30 days or greater found. Purging completed!')
            ->assertExitCode(0);

        Bus::assertDispatchedSync(PurgeBots::class);
    }

    /** @test */
    public function it_finds_multiple_bots()
    {
        $thread = Thread::factory()->group()->create();
        Bot::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->sequence(
                ['deleted_at' => now()->subDays(8)],
                ['deleted_at' => now()->subDays(10)],
            )
            ->count(2)
            ->create();

        $this->artisan('messenger:purge:bots', [
            '--days' => 7,
        ])
            ->expectsOutput('2 bots archived 7 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeBots::class);
    }

    /** @test */
    public function it_dispatches_multiple_jobs_chunking_per_100()
    {
        $thread = Thread::factory()->group()->create();
        Bot::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->count(200)
            ->trashed(now()->subYear())
            ->create();

        $this->artisan('messenger:purge:bots')
            ->expectsOutput('200 bots archived 30 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatchedTimes(PurgeBots::class, 2);
    }
}
