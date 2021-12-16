<?php

namespace RTippin\Messenger\Tests\Brokers;

use RTippin\Messenger\Brokers\FriendBroker;
use RTippin\Messenger\Brokers\NullFriendBroker;
use RTippin\Messenger\Contracts\FriendDriver;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class FriendBrokerTest extends FeatureTestCase
{
    /** @test */
    public function it_uses_default_friend_broker()
    {
        $this->assertInstanceOf(FriendBroker::class, app(FriendDriver::class));
    }

    /** @test */
    public function it_can_use_null_friend_broker()
    {
        Messenger::setFriendDriver(NullFriendBroker::class);

        $this->assertInstanceOf(NullFriendBroker::class, app(FriendDriver::class));
    }
}
