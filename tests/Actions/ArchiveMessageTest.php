<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Messages\ArchiveMessage;
use RTippin\Messenger\Broadcasting\MessageArchivedBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\MessageArchivedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class ArchiveMessageTest extends FeatureTestCase
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
        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_soft_deletes_message()
    {
        app(ArchiveMessage::class)->withoutDispatches()->execute(
            $this->private,
            $this->message
        );

        $this->assertSoftDeleted('messages', [
            'id' => $this->message->id,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        Event::fake([
            MessageArchivedBroadcast::class,
            MessageArchivedEvent::class,
        ]);

        app(ArchiveMessage::class)->execute(
            $this->private,
            $this->message
        );

        Event::assertDispatched(function (MessageArchivedBroadcast $event) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($this->message->id, $event->broadcastWith()['message_id']);

            return true;
        });
        Event::assertDispatched(function (MessageArchivedEvent $event) {
            return $this->message->id === $event->message->id;
        });
    }
}
