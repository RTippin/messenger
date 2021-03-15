<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class MessageTest extends FeatureTestCase
{
    private MessengerProvider $tippin;
    private Thread $group;
    private Message $message;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->group = $this->createGroupThread($this->tippin);
        $this->message = $this->createMessage($this->group, $this->tippin);
    }

    /** @test */
    public function it_exists()
    {
        $this->assertDatabaseCount('messages', 1);
        $this->assertDatabaseHas('messages', [
            'id' => $this->message->id,
        ]);
        $this->assertInstanceOf(Message::class, $this->message);
        $this->assertSame(1, Message::text()->count());
        $this->assertSame(1, Message::nonSystem()->count());
        $this->assertSame(0, Message::system()->count());
        $this->assertSame(0, Message::image()->count());
        $this->assertSame(0, Message::document()->count());
        $this->assertSame(0, Message::audio()->count());
        $this->assertSame('MESSAGE', $this->message->getTypeVerbose());
        $this->assertTrue($this->message->isText());
        $this->assertFalse($this->message->isEdited());
        $this->assertFalse($this->message->isSystemMessage());
        $this->assertFalse($this->message->isImage());
        $this->assertFalse($this->message->isDocument());
        $this->assertFalse($this->message->hasTemporaryId());
    }

    /** @test */
    public function it_cast_attributes()
    {
        $this->message->delete();

        $this->assertInstanceOf(Carbon::class, $this->message->created_at);
        $this->assertInstanceOf(Carbon::class, $this->message->updated_at);
        $this->assertInstanceOf(Carbon::class, $this->message->deleted_at);
        $this->assertSame(0, $this->message->type);
    }

    /** @test */
    public function it_sets_temporary_id()
    {
        $this->message->setTemporaryId('1234');

        $this->assertTrue($this->message->hasTemporaryId());
        $this->assertSame('1234', $this->message->temporaryId());
    }

    /** @test */
    public function it_has_relations()
    {
        $this->assertSame($this->tippin->getKey(), $this->message->owner->getKey());
        $this->assertSame($this->group->id, $this->message->thread->id);
        $this->assertInstanceOf(Thread::class, $this->message->thread);
        $this->assertInstanceOf(MessengerProvider::class, $this->message->owner);
        $this->assertInstanceOf(Collection::class, $this->message->edits);
    }

    /** @test */
    public function owner_returns_ghost_if_not_found()
    {
        $this->message->update([
            'owner_id' => 404,
        ]);

        $this->assertInstanceOf(GhostUser::class, $this->message->owner);
    }

    /** @test */
    public function it_has_storage_disk()
    {
        $this->assertSame('messenger', $this->message->getStorageDisk());
    }

    /** @test */
    public function it_has_storage_directory()
    {
        $this->assertSame("threads/{$this->group->id}", $this->message->getStorageDirectory());
    }

    /** @test */
    public function it_is_edited()
    {
        $this->message->update([
            'updated_at' => now()->addMinutes(10),
        ]);

        $this->assertTrue($this->message->isEdited());
        $this->assertSame("/api/messenger/threads/{$this->group->id}/messages/{$this->message->id}/history", $this->message->getEditHistoryRoute());
    }

    /** @test */
    public function image_message()
    {
        $this->message->update([
            'type' => 1,
            'body' => 'test.png',
        ]);

        $this->assertTrue($this->message->isImage());
        $this->assertFalse($this->message->isSystemMessage());
        $this->assertFalse($this->message->isDocument());
        $this->assertFalse($this->message->isText());
        $this->assertFalse($this->message->isAudio());
        $this->assertSame(1, Message::image()->count());
        $this->assertSame('IMAGE_MESSAGE', $this->message->getTypeVerbose());
        $this->assertSame("threads/{$this->group->id}/images/test.png", $this->message->getImagePath());
        $this->assertSame("/messenger/threads/{$this->group->id}/gallery/{$this->message->id}/sm/test.png", $this->message->getImageViewRoute());
        $this->assertSame("/api/messenger/threads/{$this->group->id}/gallery/{$this->message->id}/lg/test.png", $this->message->getImageViewRoute('lg', true));
    }

    /** @test */
    public function document_message()
    {
        $this->message->update([
            'type' => 2,
            'body' => 'test.pdf',
        ]);

        $this->assertTrue($this->message->isDocument());
        $this->assertFalse($this->message->isSystemMessage());
        $this->assertFalse($this->message->isImage());
        $this->assertFalse($this->message->isText());
        $this->assertFalse($this->message->isAudio());
        $this->assertSame(1, Message::document()->count());
        $this->assertSame('DOCUMENT_MESSAGE', $this->message->getTypeVerbose());
        $this->assertSame("threads/{$this->group->id}/documents/test.pdf", $this->message->getDocumentPath());
        $this->assertSame("/messenger/threads/{$this->group->id}/files/{$this->message->id}/test.pdf", $this->message->getDocumentDownloadRoute());
        $this->assertSame("/api/messenger/threads/{$this->group->id}/files/{$this->message->id}/test.pdf", $this->message->getDocumentDownloadRoute(true));
    }

    /** @test */
    public function audio_message()
    {
        $this->message->update([
            'type' => 3,
            'body' => 'test.mp3',
        ]);

        $this->assertTrue($this->message->isAudio());
        $this->assertFalse($this->message->isDocument());
        $this->assertFalse($this->message->isSystemMessage());
        $this->assertFalse($this->message->isImage());
        $this->assertFalse($this->message->isText());
        $this->assertSame(1, Message::audio()->count());
        $this->assertSame('AUDIO_MESSAGE', $this->message->getTypeVerbose());
        $this->assertSame("threads/{$this->group->id}/audio/test.mp3", $this->message->getAudioPath());
        $this->assertSame("/messenger/threads/{$this->group->id}/audio/{$this->message->id}/test.mp3", $this->message->getAudioDownloadRoute());
        $this->assertSame("/api/messenger/threads/{$this->group->id}/audio/{$this->message->id}/test.mp3", $this->message->getAudioDownloadRoute(true));
    }

    /** @test */
    public function system_message()
    {
        $this->message->update([
            'type' => 93,
            'body' => 'created First Test Group.',
        ]);

        $this->assertTrue($this->message->isSystemMessage());
        $this->assertFalse($this->message->isDocument());
        $this->assertFalse($this->message->isImage());
        $this->assertFalse($this->message->isText());
        $this->assertFalse($this->message->isAudio());
        $this->assertSame(1, Message::system()->count());
        $this->assertSame('GROUP_CREATED', $this->message->getTypeVerbose());
    }
}
