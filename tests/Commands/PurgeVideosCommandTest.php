<?php

namespace RTippin\Messenger\Tests\Commands;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\Bus;
use RTippin\Messenger\Jobs\PurgeAudioMessages;
use RTippin\Messenger\Jobs\PurgeVideoMessages;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PurgeVideosCommandTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    /** @test */
    public function it_doesnt_find_video()
    {
        $this->artisan('messenger:purge:video')
            ->expectsOutput('No video messages archived 30 days or greater found.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(PurgeVideoMessages::class);
    }

    /** @test */
    public function it_can_set_days()
    {
        $this->artisan('messenger:purge:video', [
            '--days' => 10,
        ])
            ->expectsOutput('No video messages archived 10 days or greater found.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(PurgeVideoMessages::class);
    }

    /** @test */
    public function it_dispatches_job()
    {
        Message::factory()
            ->for(Thread::factory()->create())
            ->owner($this->tippin)
            ->video()
            ->create(['deleted_at' => now()->subMonths(2)]);

        $this->artisan('messenger:purge:video')
            ->expectsOutput('1 video messages archived 30 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeVideoMessages::class);
    }

    /** @test */
    public function it_runs_job_now()
    {
        Message::factory()
            ->for(Thread::factory()->create())
            ->owner($this->tippin)
            ->video()
            ->create(['deleted_at' => now()->subMonths(2)]);

        $this->artisan('messenger:purge:video', [
            '--now' => true,
        ])
            ->expectsOutput('1 video messages archived 30 days or greater found. Purging completed!')
            ->assertExitCode(0);

        Bus::assertDispatchedSync(PurgeVideoMessages::class);
    }

    /** @test */
    public function it_finds_multiple_video_files()
    {
        Message::factory()
            ->for(Thread::factory()->create())
            ->owner($this->tippin)
            ->video()
            ->state(new Sequence(
                ['deleted_at' => now()->subDays(8)],
                ['deleted_at' => now()->subDays(10)],
            ))
            ->count(2)
            ->create();

        $this->artisan('messenger:purge:video', [
            '--days' => 7,
        ])
            ->expectsOutput('2 video messages archived 7 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeVideoMessages::class);
    }

    /** @test */
    public function it_dispatches_multiple_jobs_chunking_per_100()
    {
        $thread = Thread::factory()->create();
        Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->video()
            ->count(200)
            ->create([
                'deleted_at' => now()->subYear(),
            ]);

        $this->artisan('messenger:purge:video')
            ->expectsOutput('200 video messages archived 30 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatchedTimes(PurgeVideoMessages::class, 2);
    }
}
