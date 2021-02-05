<?php

namespace RTippin\Messenger\Tests\Actions;

use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class UpdateMessageTest extends FeatureTestCase
{
    private Thread $private;

    private Message $message;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->message = $this->createMessage($this->private, $this->tippin);
    }
    /** @test */
    public function update_message_updates_message()
    {
        //TODO
    }
}
