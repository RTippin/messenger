<?php

namespace RTippin\Messenger\Tests\Commands;

use Illuminate\Support\Facades\Bus;
use RTippin\Messenger\Jobs\PurgeImageMessages;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PurgeImagesCommandTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    /** @test */
    public function it_doesnt_find_images()
    {
        $this->artisan('messenger:purge:images')
            ->expectsOutput('No image messages archived 30 days or greater found.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(PurgeImageMessages::class);
    }

    /** @test */
    public function it_can_set_days()
    {
        $this->artisan('messenger:purge:images', [
            '--days' => 10,
        ])
            ->expectsOutput('No image messages archived 10 days or greater found.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(PurgeImageMessages::class);
    }

    /** @test */
    public function it_dispatches_job()
    {
        Message::factory()
            ->for(Thread::factory()->create())
            ->owner($this->tippin)
            ->image()
            ->trashed(now()->subMonths(2))
            ->create();

        $this->artisan('messenger:purge:images')
            ->expectsOutput('1 image messages archived 30 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeImageMessages::class);
    }

    /** @test */
    public function it_runs_job_now()
    {
        Message::factory()
            ->for(Thread::factory()->create())
            ->owner($this->tippin)
            ->image()
            ->trashed(now()->subMonths(2))
            ->create();

        $this->artisan('messenger:purge:images', [
            '--now' => true,
        ])
            ->expectsOutput('1 image messages archived 30 days or greater found. Purging completed!')
            ->assertExitCode(0);

        Bus::assertDispatchedSync(PurgeImageMessages::class);
    }

    /** @test */
    public function it_finds_multiple_images()
    {
        Message::factory()
            ->for(Thread::factory()->create())
            ->owner($this->tippin)
            ->image()
            ->sequence(
                ['deleted_at' => now()->subDays(8)],
                ['deleted_at' => now()->subDays(10)],
            )
            ->count(2)
            ->create();

        $this->artisan('messenger:purge:images', [
            '--days' => 7,
        ])
            ->expectsOutput('2 image messages archived 7 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeImageMessages::class);
    }

    /** @test */
    public function it_dispatches_multiple_jobs_chunking_per_100()
    {
        $thread = Thread::factory()->create();
        Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->image()
            ->count(200)
            ->trashed(now()->subYear())
            ->create();

        $this->artisan('messenger:purge:images')
            ->expectsOutput('200 image messages archived 30 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatchedTimes(PurgeImageMessages::class, 2);
    }
}
