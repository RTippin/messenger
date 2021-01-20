<?php

namespace RTippin\Messenger\Tests\Actions;

use RTippin\Messenger\Actions\Calls\CallBrokerSetup;
use RTippin\Messenger\Contracts\VideoDriver;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;
use RTippin\Messenger\Tests\stubs\TestVideoBroker;

class CallBrokerSetupTest extends FeatureTestCase
{
    private Thread $group;

    private Call $call;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $this->group = $this->createGroupThread($tippin);

        $this->call = $this->group->calls()->create([
            'type' => 1,
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
            'call_ended' => null,
            'setup_complete' => false,
            'room_id' => null,
            'room_pin' => null,
            'room_secret' => null,
            'payload' => null,
        ]);
    }

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app->singleton(
            VideoDriver::class,
            TestVideoBroker::class
        );
    }

    /** @test */
    public function call_setup_updates_call()
    {
        app(CallBrokerSetup::class)->execute(
            $this->group,
            $this->call
        );

        $this->assertDatabaseHas('calls', [
            'id' => $this->call->id,
            'setup_complete' => true,
            'room_id' => '123456',
            'room_pin' => 'TEST-PIN',
            'room_secret' => 'TEST-SECRET',
            'payload' => 'TEST-EXTRA-PAYLOAD',
        ]);
    }
}
