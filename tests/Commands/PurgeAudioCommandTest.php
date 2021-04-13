<?php

namespace RTippin\Messenger\Tests\Commands;

use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\PurgeAudioMessages;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PurgeAudioCommandTest extends FeatureTestCase
{
    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->createGroupThread($this->tippin);
        Storage::fake(Messenger::getThreadStorage('disk'));
        Bus::fake();
    }

    /** @test */
    public function it_doesnt_find_audio()
    {
        $this->artisan('messenger:purge:audio')
            ->expectsOutput('No audio messages archived 30 days or greater found.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(PurgeAudioMessages::class);
    }

    /** @test */
    public function it_can_set_days()
    {
        $this->artisan('messenger:purge:audio', [
            '--days' => 10,
        ])
            ->expectsOutput('No audio messages archived 10 days or greater found.')
            ->assertExitCode(0);

        Bus::assertNotDispatched(PurgeAudioMessages::class);
    }

    /** @test */
    public function it_dispatches_job()
    {
        Message::factory()
            ->for($this->group)
            ->owner($this->tippin)
            ->audio()
            ->create(['deleted_at' => now()->subMonths(2)]);

        $this->artisan('messenger:purge:audio')
            ->expectsOutput('1 audio messages archived 30 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeAudioMessages::class);
    }

    /** @test */
    public function it_runs_job_now()
    {
        Message::factory()
            ->for($this->group)
            ->owner($this->tippin)
            ->audio()
            ->create(['deleted_at' => now()->subMonths(2)]);

        $this->artisan('messenger:purge:audio', [
            '--now' => true,
        ])
            ->expectsOutput('1 audio messages archived 30 days or greater found. Purging completed!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeAudioMessages::class);
    }

    /** @test */
    public function it_finds_multiple_documents()
    {
        Message::factory()
            ->for($this->group)
            ->owner($this->tippin)
            ->audio()
            ->state(new Sequence(
                ['deleted_at' => now()->subDays(8)],
                ['deleted_at' => now()->subDays(10)],
            ))
            ->count(2)
            ->create();

        $this->artisan('messenger:purge:audio', [
            '--days' => 7,
        ])
            ->expectsOutput('2 audio messages archived 7 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeAudioMessages::class);
    }
}
