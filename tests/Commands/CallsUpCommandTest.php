<?php

namespace RTippin\Messenger\Tests\Commands;

use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\MessengerTestCase;

class CallsUpCommandTest extends MessengerTestCase
{
    /** @test */
    public function it_does_nothing_if_calling_disabled()
    {
        Messenger::setCalling(false);

        $this->artisan('messenger:calls:up')
            ->expectsOutput('Call system currently disabled.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_does_nothing_if_calling_already_up()
    {
        $this->artisan('messenger:calls:up')
            ->expectsOutput('Call system is already online.')
            ->assertExitCode(0);
    }

    /** @test */
    public function it_removes_cache_lockout_key()
    {
        Messenger::disableCallsTemporarily(1);

        $this->artisan('messenger:calls:up')
            ->expectsOutput('Call system is now online.')
            ->assertExitCode(0);

        $this->assertFalse(Cache::has('messenger:calls:down'));
    }
}
