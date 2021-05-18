<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\Messages\PurgeDocumentMessages;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PurgeDocumentMessagesTest extends FeatureTestCase
{
    /** @test */
    public function it_removes_messages()
    {
        $thread = Thread::factory()->create();
        $document1 = Message::factory()->for($thread)->owner($this->tippin)->document()->create();
        $document2 = Message::factory()->for($thread)->owner($this->tippin)->document()->create();

        app(PurgeDocumentMessages::class)->execute(Message::document()->get());

        $this->assertDatabaseMissing('messages', [
            'id' => $document1->id,
        ]);
        $this->assertDatabaseMissing('messages', [
            'id' => $document2->id,
        ]);
    }

    /** @test */
    public function it_removes_documents_from_disk()
    {
        $thread = Thread::factory()->create();
        $document1 = Message::factory()->for($thread)->owner($this->tippin)->document()->create(['body' => 'test.pdf']);
        $document2 = Message::factory()->for($thread)->owner($this->tippin)->document()->create(['body' => 'foo.pdf']);
        UploadedFile::fake()
            ->create('test.pdf', 500, 'application/pdf')
            ->storeAs($thread->getDocumentsDirectory(), 'test.pdf', [
                'disk' => 'messenger',
            ]);
        UploadedFile::fake()
            ->create('foo.pdf', 500, 'application/pdf')
            ->storeAs($thread->getDocumentsDirectory(), 'foo.pdf', [
                'disk' => 'messenger',
            ]);

        app(PurgeDocumentMessages::class)->execute(Message::document()->get());

        Storage::disk('messenger')->assertMissing($document1->getDocumentPath());
        Storage::disk('messenger')->assertMissing($document2->getDocumentPath());
    }
}
