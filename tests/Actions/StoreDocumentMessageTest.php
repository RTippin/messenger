<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\Messages\StoreDocumentMessage;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreDocumentMessageTest extends FeatureTestCase
{
    private Thread $private;

    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    private string $disk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->disk = Messenger::getThreadStorage('disk');

        Messenger::setProvider($this->tippin);

        Storage::fake($this->disk);
    }

    /** @test */
    public function store_document_stores_message()
    {
        app(StoreDocumentMessage::class)->withoutDispatches()->execute(
            $this->private,
            UploadedFile::fake()->create('test.pdf', 500, 'application/pdf')
        );

        $this->assertDatabaseHas('messages', [
            'thread_id' => $this->private->id,
            'type' => 2,
        ]);
    }

    /** @test */
    public function store_document_stores_document_file()
    {
        app(StoreDocumentMessage::class)->withoutDispatches()->execute(
            $this->private,
            UploadedFile::fake()->create('test.pdf', 500, 'application/pdf')
        );

        Storage::disk($this->disk)->assertExists(Message::document()->first()->getDocumentPath());
    }

    /** @test */
    public function store_document_sets_temporary_id_on_message()
    {
        $action = app(StoreDocumentMessage::class)->withoutDispatches()->execute(
            $this->private,
            UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
            '123-456-789'
        );

        $this->assertSame('123-456-789', $action->getMessage()->temporaryId());
    }

    /** @test */
    public function store_document_updates_thread_and_participant_timestamps()
    {
        $updated = now()->addMinutes(5);

        Carbon::setTestNow($updated);

        app(StoreDocumentMessage::class)->withoutDispatches()->execute(
            $this->private,
            UploadedFile::fake()->create('test.pdf', 500, 'application/pdf')
        );

        $participant = $this->private->participants()
            ->where('owner_id', '=', $this->tippin->getKey())
            ->where('owner_type', '=', get_class($this->tippin))
            ->first();

        $this->assertDatabaseHas('threads', [
            'id' => $this->private->id,
            'updated_at' => $updated,
        ]);

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'last_read' => $updated,
        ]);
    }

    /** @test */
    public function store_document_fires_events()
    {
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        app(StoreDocumentMessage::class)->execute(
            $this->private,
            UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
            '123-456-789'
        );

        Event::assertDispatched(function (NewMessageBroadcast $event) {
            $this->assertContains('private-user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($this->private->id, $event->broadcastWith()['thread_id']);
            $this->assertSame('123-456-789', $event->broadcastWith()['temporary_id']);

            return true;
        });

        Event::assertDispatched(function (NewMessageEvent $event) {
            return $this->private->id === $event->message->thread_id;
        });
    }
}
