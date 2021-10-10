<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Messages\ArchiveMessage;
use RTippin\Messenger\Broadcasting\MessageArchivedBroadcast;
use RTippin\Messenger\Events\MessageArchivedEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class ArchiveMessageTest extends FeatureTestCase
{
    use BroadcastLogger;

    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_soft_deletes_message_and_removes_from_reply_cache()
    {
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $cache = Cache::spy();

        app(ArchiveMessage::class)->execute($thread, $message);

        $cache->shouldHaveReceived('forget')->with('reply:message:'.$message->id);
        $this->assertSoftDeleted('messages', [
            'id' => $message->id,
        ]);
    }

    /** @test */
    public function it_fires_events()
    {
        BaseMessengerAction::enableEvents();
        Event::fake([
            MessageArchivedBroadcast::class,
            MessageArchivedEvent::class,
        ]);
        $thread = $this->createPrivateThread($this->tippin, $this->doe);
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        app(ArchiveMessage::class)->execute($thread, $message);

        Event::assertDispatched(function (MessageArchivedBroadcast $event) use ($message) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($message->id, $event->broadcastWith()['message_id']);

            return true;
        });
        Event::assertDispatched(function (MessageArchivedEvent $event) use ($message) {
            return $message->id === $event->message->id;
        });
        $this->logBroadcast(MessageArchivedBroadcast::class);
    }
}
