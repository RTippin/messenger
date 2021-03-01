<?php

namespace RTippin\Messenger\Tests\Commands;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Jobs\EndCalls;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallsDownCommandTest extends FeatureTestCase
{
    private MessengerProvider $tippin;

    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->group = $this->createGroupThread($this->tippin);

        Bus::fake();
    }

    /** @test */
    public function down_when_no_calls_sets_cache_lockout_and_no_dispatches()
    {
        $this->artisan('messenger:calls:down')
            ->expectsOutput('No active calls to end found.')
            ->expectsOutput('Call system is now down for 30 minutes.')
            ->assertExitCode(0);

        $this->assertTrue(Cache::has('messenger:calls:down'));

        Bus::assertNotDispatched(EndCalls::class);
    }

    /** @test */
    public function down_does_nothing_when_calling_disabled_in_config()
    {
        Messenger::setCalling(false);

        $this->artisan('messenger:calls:down')
            ->expectsOutput('Call system currently disabled.')
            ->assertExitCode(0);

        $this->assertFalse(Cache::has('messenger:calls:down'));

        Bus::assertNotDispatched(EndCalls::class);
    }

    /** @test */
    public function down_does_nothing_when_calling_already_down()
    {
        Messenger::disableCallsTemporarily(1);

        $this->artisan('messenger:calls:down')
            ->expectsOutput('Call system is already shutdown.')
            ->assertExitCode(0);

        $this->assertTrue(Cache::has('messenger:calls:down'));

        Bus::assertNotDispatched(EndCalls::class);
    }

    /** @test */
    public function down_dispatches_job_and_sets_cache_lockout()
    {
        $this->createCall($this->group, $this->tippin);

        $this->artisan('messenger:calls:down')
            ->expectsOutput('1 active calls found. End calls dispatched!')
            ->expectsOutput('Call system is now down for 30 minutes.')
            ->assertExitCode(0);

        $this->assertTrue(Cache::has('messenger:calls:down'));

        Bus::assertDispatched(EndCalls::class);
    }

    /** @test */
    public function down_dispatches_job_now_and_sets_cache_lockout()
    {
        $this->createCall($this->group, $this->tippin);

        $this->artisan('messenger:calls:down', [
            '--now' => true,
        ])
            ->expectsOutput('1 active calls found. End calls completed!')
            ->expectsOutput('Call system is now down for 30 minutes.')
            ->assertExitCode(0);

        $this->assertTrue(Cache::has('messenger:calls:down'));

        Bus::assertDispatched(EndCalls::class);
    }

    /** @test */
    public function down_can_set_cache_lockout_time()
    {
        $this->artisan('messenger:calls:down', [
            '--duration' => 60,
        ])
            ->expectsOutput('No active calls to end found.')
            ->expectsOutput('Call system is now down for 60 minutes.')
            ->assertExitCode(0);

        $this->assertTrue(Cache::has('messenger:calls:down'));

        Bus::assertNotDispatched(EndCalls::class);
    }

    /** @test */
    public function down_finds_multiple_calls()
    {
        $this->createCall($this->group, $this->tippin);

        $this->createCall($this->group, $this->tippin);

        $this->artisan('messenger:calls:down')
            ->expectsOutput('2 active calls found. End calls dispatched!')
            ->expectsOutput('Call system is now down for 30 minutes.')
            ->assertExitCode(0);

        $this->assertTrue(Cache::has('messenger:calls:down'));

        Bus::assertDispatched(EndCalls::class);
    }
}
