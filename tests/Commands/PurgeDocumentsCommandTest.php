<?php

namespace RTippin\Messenger\Tests\Commands;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\PurgeDocumentMessages;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PurgeDocumentsCommandTest extends FeatureTestCase
{
    private MessengerProvider $tippin;
    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->group = $this->createGroupThread($this->tippin);
        Storage::fake(Messenger::getThreadStorage('disk'));
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
        $this->group->messages()->create([
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'type' => 2,
            'body' => 'test.pdf',
            'deleted_at' => now()->subMonths(2),
        ]);

        $this->artisan('messenger:purge:documents')
            ->expectsOutput('1 document messages archived 30 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeDocumentMessages::class);
    }

    /** @test */
    public function it_runs_job_now()
    {
        $this->group->messages()->create([
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'type' => 2,
            'body' => 'test.pdf',
            'deleted_at' => now()->subMonths(2),
        ]);

        $this->artisan('messenger:purge:documents', [
            '--now' => true,
        ])
            ->expectsOutput('1 document messages archived 30 days or greater found. Purging completed!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeDocumentMessages::class);
    }

    /** @test */
    public function it_finds_multiple_documents()
    {
        $this->group->messages()->create([
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'type' => 2,
            'body' => 'test.pdf',
            'deleted_at' => now()->subDays(10),
        ]);
        $this->group->messages()->create([
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'type' => 2,
            'body' => 'test.pdf',
            'deleted_at' => now()->subDays(8),
        ]);

        $this->artisan('messenger:purge:documents', [
            '--days' => 7,
        ])
            ->expectsOutput('2 document messages archived 7 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeDocumentMessages::class);
    }
}
