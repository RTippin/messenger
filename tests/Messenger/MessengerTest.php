<?php


namespace RTippin\Messenger\Tests\Messenger;
use RTippin\Messenger\Facades\Messenger as MessengerFacade;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Tests\MessengerTestCase;

class MessengerTest extends MessengerTestCase
{
    /** @test */
    public function messenger_facade_same_instance_as_container()
    {
        $this->assertSame(app(Messenger::class), MessengerFacade::instance());
    }

    /** @test */
    public function messenger_helper_same_instance_as_container()
    {
        $this->assertSame(app(Messenger::class), messenger());
    }
}
