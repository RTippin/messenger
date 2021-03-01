<?php

namespace RTippin\Messenger\Tests\Commands;

use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\MessengerTestCase;

class CallsUpCommandTest extends MessengerTestCase
{
    /** @test */
    public function up_does_nothing_when_calling_disabled_in_config()
    {
        Messenger::setCalling(false);

        $this->artisan('messenger:calls:up')
            ->expectsOutput('Call system currently disabled.')
            ->assertExitCode(0);
    }

    /** @test */
    public function up_does_nothing_when_calling_already_up()
    {
        $this->assertFalse(Cache::has('messenger:calls:down'));

        $this->artisan('messenger:calls:up')
            ->expectsOutput('Call system is already online.')
            ->assertExitCode(0);
    }

    /** @test */
    public function up_removes_cache_lockout()
    {
        Messenger::disableCallsTemporarily(1);

        $this->assertTrue(Cache::has('messenger:calls:down'));

        $this->artisan('messenger:calls:up')
            ->expectsOutput('Call system is now online.')
            ->assertExitCode(0);

        $this->assertFalse(Cache::has('messenger:calls:down'));
    }
}
