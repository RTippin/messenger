<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
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

//    /** @test */
//    public function store_document_sets_temporary_id_on_message()
//    {
//        $action = app(StoreDocumentMessage::class)->withoutDispatches()->execute(
//            $this->private,
//            UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
//            '123-456-789'
//        );
//
//        $this->assertSame('123-456-789', $action->getMessage()->temporaryId());
//    }

//    /** @test */
//    public function user_can_send_document_message()
//    {
//        Storage::fake($this->disk);
//
//        $tippin = $this->userTippin();
//
//        $this->expectsEvents([
//            NewMessageBroadcast::class,
//            NewMessageEvent::class,
//        ]);
//
//        $this->actingAs($tippin);
//
//        $this->postJson(route('api.messenger.threads.documents.store', [
//            'thread' => $this->private->id,
//        ]), [
//            'document' => UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
//            'temporary_id' => '123-456-789',
//        ])
//            ->assertSuccessful()
//            ->assertJson([
//                'thread_id' => $this->private->id,
//                'temporary_id' => '123-456-789',
//                'type' => 2,
//                'type_verbose' => 'DOCUMENT_MESSAGE',
//                'owner' => [
//                    'provider_id' => $tippin->getKey(),
//                    'provider_alias' => 'user',
//                    'name' => 'Richard Tippin',
//                ],
//            ]);
//
//        Storage::disk($this->private->getStorageDisk())
//            ->assertExists($this->private->messages()->document()->first()->getDocumentPath());
//    }
}
