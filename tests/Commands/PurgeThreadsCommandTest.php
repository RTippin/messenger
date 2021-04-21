<?php

namespace RTippin\Messenger\Tests\Commands;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\PurgeThreads;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PurgeThreadsCommandTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Messenger::getThreadStorage('disk'));
        Bus::fake();
    }

    /** @test */
    public function it_doesnt_find_threads()
    {
        $this->artisan('messenger:purge:threads')
            ->expectsOutput('No threads archived 30 days or greater found.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(PurgeThreads::class);
    }

    /** @test */
    public function it_can_set_days()
    {
        $this->artisan('messenger:purge:threads', [
            '--days' => 10,
        ])
            ->expectsOutput('No threads archived 10 days or greater found.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(PurgeThreads::class);
    }

    /** @test */
    public function it_dispatches_job()
    {
        Thread::factory()->create(['deleted_at' => now()->subMonths(2)]);

        $this->artisan('messenger:purge:threads')
            ->expectsOutput('1 threads archived 30 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeThreads::class);
    }

    /** @test */
    public function it_runs_job_now()
    {
        Thread::factory()->create(['deleted_at' => now()->subMonths(2)]);

        $this->artisan('messenger:purge:threads', [
            '--now' => true,
        ])
            ->expectsOutput('1 threads archived 30 days or greater found. Purging completed!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeThreads::class);
    }

    /** @test */
    public function it_finds_multiple_threads()
    {
        Thread::factory()
            ->state(new Sequence(
                ['deleted_at' => now()->subDays(8)],
                ['deleted_at' => now()->subDays(10)],
            ))
            ->count(2)
            ->create();

        $this->artisan('messenger:purge:threads', [
            '--days' => 7,
        ])
            ->expectsOutput('2 threads archived 7 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeThreads::class);
    }

    /** @test */
    public function it_dispatches_multiple_jobs_chunking_per_100()
    {
        Thread::factory()
            ->count(200)
            ->create([
                'deleted_at' => now()->subYear(),
            ]);

        $this->artisan('messenger:purge:threads')
            ->expectsOutput('200 threads archived 30 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatchedTimes(PurgeThreads::class, 2);
    }
}
