<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\Messages\PurgeDocumentMessages;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PurgeDocumentMessagesTest extends FeatureTestCase
{
    private Thread $group;

    private Message $document1;

    private Message $document2;

    private string $disk;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $this->group = $this->createGroupThread($tippin);

        $this->disk = Messenger::getThreadStorage('disk');

        Storage::fake($this->disk);

        $this->document1 = $this->group->messages()->create([
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
            'type' => 2,
            'body' => 'test.pdf',
        ]);

        UploadedFile::fake()
            ->create('test.pdf', 500, 'application/pdf')
            ->storeAs($this->group->getStorageDirectory().'/documents', 'test.pdf', [
                'disk' => $this->disk,
            ]);

        $this->document2 = $this->group->messages()->create([
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
            'type' => 2,
            'body' => 'foo.pdf',
        ]);

        UploadedFile::fake()
            ->create('foo.pdf', 500, 'application/pdf')
            ->storeAs($this->group->getStorageDirectory().'/documents', 'foo.pdf', [
                'disk' => $this->disk,
            ]);
    }

    /** @test */
    public function purge_documents_removes_messages_from_database()
    {
        app(PurgeDocumentMessages::class)->execute(Message::document()->get());

        $this->assertDatabaseMissing('messages', [
            'id' => $this->document1->id,
        ]);

        $this->assertDatabaseMissing('messages', [
            'id' => $this->document2->id,
        ]);
    }

    /** @test */
    public function purge_documents_removes_stored_documents()
    {
        app(PurgeDocumentMessages::class)->execute(Message::document()->get());

        Storage::disk($this->disk)->assertMissing($this->document1->getDocumentPath());

        Storage::disk($this->disk)->assertMissing($this->document2->getDocumentPath());
    }
}
