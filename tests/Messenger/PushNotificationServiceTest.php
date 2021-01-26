<?php

namespace RTippin\Messenger\Tests\Messenger;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PushNotificationServiceTest extends FeatureTestCase
{
    private Thread $group;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    private MessengerProvider $developers;

    const WITH = [
        'data' => 1234,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->developers = $this->companyDevelopers();

        $this->group = $this->createGroupThread($this->tippin, $this->doe, $this->developers);
    }

    /** @test */
    public function broadcast_driver_using_default_broadcast_broker()
    {
        $this->assertTrue(true);
    }
}
