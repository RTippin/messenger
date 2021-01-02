<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class DocumentMessageTest extends FeatureTestCase
{
    private Thread $private;

    protected function setUp(): void
    {
        parent::setUp();

        $this->private = $this->createPrivateThread(
            $this->userTippin(),
            $this->userDoe()
        );
    }

    /** @test */
    public function user_can_send_document_message()
    {
        Storage::fake($this->private->getStorageDisk());

        $tippin = $this->userTippin();

        $this->expectsEvents([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.documents.store', [
            'thread' => $this->private->id,
        ]), [
            'document' => UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
            'temporary_id' => '123-456-789',
        ])
            ->assertSuccessful()
            ->assertJson([
                'thread_id' => $this->private->id,
                'temporary_id' => '123-456-789',
                'type' => 2,
                'type_verbose' => 'DOCUMENT_MESSAGE',
                'owner' => [
                    'provider_id' => $tippin->getKey(),
                    'provider_alias' => 'user',
                    'name' => 'Richard Tippin',
                ],
            ]);

        Storage::disk($this->private->getStorageDisk())
            ->assertExists($this->private->messages()->document()->first()->getDocumentPath());
    }

    /** @test */
    public function user_forbidden_to_send_document_message_when_disabled_from_config()
    {
        Messenger::setMessageDocumentUpload(false);

        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.documents.store', [
            'thread' => $this->private->id,
        ]), [
            'document' => UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'),
            'temporary_id' => '123-456-789',
        ])
            ->assertForbidden();
    }

    /**
     * @test
     * @dataProvider documentValidation
     * @param $documentValue
     */
    public function send_document_message_validates_image_file($documentValue)
    {
        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.documents.store', [
            'thread' => $this->private->id,
        ]), [
            'document' => $documentValue,
            'temporary_id' => '123-456-789',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('document');
    }

    public function documentValidation(): array
    {
        return [
            'Document cannot be empty' => [''],
            'Document cannot be integer' => [5],
            'Document cannot be null' => [null],
            'Document cannot be an array' => [[1, 2]],
            'Document cannot be a movie' => [UploadedFile::fake()->create('movie.mov', 500, 'video/quicktime')],
            'Document must be under 10mb' => [UploadedFile::fake()->create('test.pdf', 11000, 'application/pdf')],
            'Document cannot be an image' => [UploadedFile::fake()->image('picture.png')],
        ];
    }
}
