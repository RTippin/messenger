<?php

namespace RTippin\Messenger\Tests\Commands;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Definitions;
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
    public function purge_command_no_archived_threads_found_default()
    {
        $this->artisan('messenger:purge:threads')
            ->expectsOutput('No threads archived 30 days or greater found.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(PurgeThreads::class);
    }

    /** @test */
    public function purge_command_no_archived_threads_found_with_days()
    {
        $this->artisan('messenger:purge:threads', [
            '--days' => 10,
        ])
            ->expectsOutput('No threads archived 10 days or greater found.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(PurgeThreads::class);
    }

    /** @test */
    public function purge_command_dispatches_job_default()
    {
        Thread::create(array_merge(Definitions::DefaultThread, [
            'deleted_at' => now()->subMonths(2),
        ]));

        $this->artisan('messenger:purge:threads')
            ->expectsOutput('1 threads archived 30 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeThreads::class);
    }

    /** @test */
    public function purge_command_runs_job_now()
    {
        Thread::create(array_merge(Definitions::DefaultThread, [
            'deleted_at' => now()->subMonths(2),
        ]));

        $this->artisan('messenger:purge:threads', [
            '--now' => true,
        ])
            ->expectsOutput('1 threads archived 30 days or greater found. Purging completed!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeThreads::class);
    }

    /** @test */
    public function purge_command_finds_multiple_archived_threads()
    {
        Thread::create(array_merge(Definitions::DefaultThread, [
            'deleted_at' => now()->subDays(10),
        ]));

        Thread::create(array_merge(Definitions::DefaultThread, [
            'deleted_at' => now()->subDays(8),
        ]));

        $this->artisan('messenger:purge:threads', [
            '--days' => 7,
        ])
            ->expectsOutput('2 threads archived 7 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeThreads::class);
    }
}
