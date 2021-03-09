<?php

namespace RTippin\Messenger\Tests\Commands;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\PurgeImageMessages;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PurgeImagesCommandTest extends FeatureTestCase
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
        $this->group->messages()->create([
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'type' => 1,
            'body' => 'test.png',
            'deleted_at' => now()->subMonths(2),
        ]);

        $this->artisan('messenger:purge:images')
            ->expectsOutput('1 image messages archived 30 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeImageMessages::class);
    }

    /** @test */
    public function it_runs_job_now()
    {
        $this->group->messages()->create([
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'type' => 1,
            'body' => 'test.png',
            'deleted_at' => now()->subMonths(2),
        ]);

        $this->artisan('messenger:purge:images', [
            '--now' => true,
        ])
            ->expectsOutput('1 image messages archived 30 days or greater found. Purging completed!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeImageMessages::class);
    }

    /** @test */
    public function it_finds_multiple_images()
    {
        $this->group->messages()->create([
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'type' => 1,
            'body' => 'test.png',
            'deleted_at' => now()->subDays(10),
        ]);
        $this->group->messages()->create([
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'type' => 1,
            'body' => 'test.png',
            'deleted_at' => now()->subDays(8),
        ]);

        $this->artisan('messenger:purge:images', [
            '--days' => 7,
        ])
            ->expectsOutput('2 image messages archived 7 days or greater found. Purging dispatched!')
            ->assertExitCode(0);

        Bus::assertDispatched(PurgeImageMessages::class);
    }
}
