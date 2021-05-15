<?php

namespace RTippin\Messenger\Tests\Actions;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RTippin\Messenger\Actions\Messages\StoreDocumentMessage;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\FileService;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreDocumentMessageTest extends FeatureTestCase
{
    private Thread $private;
    private string $disk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->private = $this->createPrivateThread($this->tippin, $this->doe);
        $this->disk = Messenger::getThreadStorage('disk');
        Messenger::setProvider($this->tippin);
        Storage::fake($this->disk);
    }

    /** @test */
    public function it_throws_exception_if_disabled()
    {
        Messenger::setMessageDocumentUpload(false);

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Document messages are currently disabled.');

        app(StoreDocumentMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'document' => UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
            ]
        );
    }

    /** @test */
    public function it_throws_exception_if_transaction_fails_and_removes_uploaded_document()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('DB Error');
        DB::shouldReceive('transaction')->andThrow(new Exception('DB Error'));
        $this->mock(FileService::class)->shouldReceive([
            'setType' => Mockery::self(),
            'setDisk' => Mockery::self(),
            'setDirectory' => Mockery::self(),
            'upload' => 'test.pdf',
            'destroy' => true,
        ]);

        app(StoreDocumentMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'document' => UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
            ]
        );
    }

    /** @test */
    public function it_stores_message()
    {
        app(StoreDocumentMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'document' => UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
            ]
        );

        $this->assertDatabaseHas('messages', [
            'thread_id' => $this->private->id,
            'type' => 2,
        ]);
    }

    /** @test */
    public function it_stores_document_file()
    {
        app(StoreDocumentMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'document' => UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
            ]
        );

        Storage::disk($this->disk)->assertExists(Message::document()->first()->getDocumentPath());
    }

    /** @test */
    public function it_sets_temporary_id_on_message()
    {
        $action = app(StoreDocumentMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'document' => UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
                'temporary_id' => '123-456-789',
            ]
        );

        $this->assertSame('123-456-789', $action->getMessage()->temporaryId());
    }

    /** @test */
    public function it_can_add_extra_data_on_message()
    {
        app(StoreDocumentMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'document' => UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
                'temporary_id' => '123-456-789',
                'extra' => ['test' => true],
            ]
        );

        $this->assertDatabaseHas('messages', [
            'thread_id' => $this->private->id,
            'type' => 2,
            'extra' => '{"test":true}',
        ]);
    }

    /** @test */
    public function it_can_reply_to_existing_message()
    {
        $message = $this->createMessage($this->private, $this->tippin);

        app(StoreDocumentMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'document' => UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
                'temporary_id' => '123-456-789',
                'reply_to_id' => $message->id,
            ]
        );

        $this->assertDatabaseHas('messages', [
            'thread_id' => $this->private->id,
            'type' => 2,
            'reply_to_id' => $message->id,
        ]);
    }

    /** @test */
    public function it_updates_thread_and_participant()
    {
        $updated = now()->addMinutes(5)->format('Y-m-d H:i:s.u');
        Carbon::setTestNow($updated);

        app(StoreDocumentMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'document' => UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
            ]
        );

        $participant = $this->private->participants()->forProvider($this->tippin)->first();

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
    public function it_fires_events()
    {
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        app(StoreDocumentMessage::class)->execute(
            $this->private,
            [
                'document' => UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
                'temporary_id' => '123-456-789',
            ]
        );

        Event::assertDispatched(function (NewMessageBroadcast $event) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($this->private->id, $event->broadcastWith()['thread_id']);
            $this->assertSame('123-456-789', $event->broadcastWith()['temporary_id']);

            return true;
        });
        Event::assertDispatched(function (NewMessageEvent $event) {
            return $this->private->id === $event->message->thread_id;
        });
    }
}
