<?php

namespace RTippin\Messenger\Tests\Commands;

use Illuminate\Support\Facades\Bus;
use RTippin\Messenger\Jobs\PurgeDocumentMessages;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PurgeDocumentsCommandTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Bus::fake();
    }

    /** @test */
    public function it_doesnt_find_documents()
    {
        $this->artisan('messenger:purge:documents')
            ->expectsOutput('No document messages archived 30 days or greater found.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(PurgeDocumentMessages::class);
    }

    /** @test */
    public function it_can_set_days()
    {
        $this->artisan('messenger:purge:documents', [
            '--days' => 10,
        ])
            ->expectsOutput('No document messages archived 10 days or greater found.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(PurgeDocumentMessages::class);
    }

    /** @test */
    public function it_dispatches_job()
    {
        Message::factory()
            ->for(Thread::factory()->create())
            ->owner($this->tippin)
            ->document()
            ->trashed(now()->subMonths(2))
            ->create();

        $this->artisan('messenger:purge:documents')
            ->expectsOutput('1 document messages archived 30 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeDocumentMessages::class);
    }

    /** @test */
    public function it_runs_job_now()
    {
        Message::factory()
            ->for(Thread::factory()->create())
            ->owner($this->tippin)
            ->document()
            ->trashed(now()->subMonths(2))
            ->create();

        $this->artisan('messenger:purge:documents', [
            '--now' => true,
        ])
            ->expectsOutput('1 document messages archived 30 days or greater found. Purging completed!')
            ->assertExitCode(0);

        Bus::assertDispatchedSync(PurgeDocumentMessages::class);
    }

    /** @test */
    public function it_finds_multiple_documents()
    {
        Message::factory()
            ->for(Thread::factory()->create())
            ->owner($this->tippin)
            ->document()
            ->sequence(
                ['deleted_at' => now()->subDays(8)],
                ['deleted_at' => now()->subDays(10)],
            )
            ->count(2)
            ->create();

        $this->artisan('messenger:purge:documents', [
            '--days' => 7,
        ])
            ->expectsOutput('2 document messages archived 7 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeDocumentMessages::class);
    }

    /** @test */
    public function it_dispatches_multiple_jobs_chunking_per_100()
    {
        $thread = Thread::factory()->create();
        Message::factory()
            ->for($thread)
            ->owner($this->tippin)
            ->document()
            ->count(200)
            ->trashed(now()->subYear())
            ->create();

        $this->artisan('messenger:purge:documents')
            ->expectsOutput('200 document messages archived 30 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatchedTimes(PurgeDocumentMessages::class, 2);
    }
}
