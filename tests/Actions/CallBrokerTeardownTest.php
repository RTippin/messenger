<?php

namespace RTippin\Messenger\Tests\Actions;

use RTippin\Messenger\Actions\Calls\CallBrokerTeardown;
use RTippin\Messenger\Contracts\VideoDriver;
use RTippin\Messenger\Exceptions\CallBrokerException;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallBrokerTeardownTest extends FeatureTestCase
{
    /** @test */
    public function it_updates_call_after_teardown()
    {
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->setup()->create();

        $this->mock(VideoDriver::class)
            ->shouldReceive('destroy')
            ->andReturn(true);

        app(CallBrokerTeardown::class)->execute($call);

        $this->assertDatabaseHas('calls', [
            'id' => $call->id,
            'teardown_complete' => true,
        ]);
    }

    /** @test */
    public function it_throws_exception_if_destroy_failed()
    {
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->setup()->create();

        $this->mock(VideoDriver::class)
            ->shouldReceive('destroy')
            ->andReturn(false);

        $this->expectException(CallBrokerException::class);
        $this->expectExceptionMessage('Teardown video provider failed.');

        app(CallBrokerTeardown::class)->execute($call);
    }
}
