<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\Messages\PurgeDocumentMessages;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Tests\FeatureTestCase;

class PurgeDocumentMessagesTest extends FeatureTestCase
{
    private Message $document1;
    private Message $document2;
    private string $disk;

    protected function setUp(): void
    {
        parent::setUp();

        $group = $this->createGroupThread($this->tippin);
        $this->disk = Messenger::getThreadStorage('disk');
        Storage::fake($this->disk);
        $this->document1 = Message::factory()->for($group)->owner($this->tippin)->document()->create(['body' => 'test.pdf']);
        UploadedFile::fake()
            ->create('test.pdf', 500, 'application/pdf')
            ->storeAs($group->getStorageDirectory().'/documents', 'test.pdf', [
                'disk' => $this->disk,
            ]);
        $this->document2 = Message::factory()->for($group)->owner($this->tippin)->document()->create(['body' => 'foo.pdf']);
        UploadedFile::fake()
            ->create('foo.pdf', 500, 'application/pdf')
            ->storeAs($group->getStorageDirectory().'/documents', 'foo.pdf', [
                'disk' => $this->disk,
            ]);
    }

    /** @test */
    public function it_removes_messages()
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
    public function it_removes_documents_from_disk()
    {
        app(PurgeDocumentMessages::class)->execute(Message::document()->get());

        Storage::disk($this->disk)->assertMissing($this->document1->getDocumentPath());
        Storage::disk($this->disk)->assertMissing($this->document2->getDocumentPath());
    }
}
