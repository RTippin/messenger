<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\MessageReaction;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class MessageTest extends FeatureTestCase
{
    /** @test */
    public function it_exists()
    {
        $message = Message::factory()->for(
            Thread::factory()->create()
        )->owner($this->tippin)->create();

        $this->assertDatabaseCount('messages', 1);
        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
        ]);
        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame(1, Message::text()->count());
        $this->assertSame(1, Message::nonSystem()->count());
        $this->assertSame(0, Message::system()->count());
        $this->assertSame(0, Message::image()->count());
        $this->assertSame(0, Message::document()->count());
        $this->assertSame(0, Message::audio()->count());
        $this->assertSame('MESSAGE', $message->getTypeVerbose());
        $this->assertTrue($message->isText());
        $this->assertTrue($message->showEmbeds());
        $this->assertFalse($message->isEdited());
        $this->assertFalse($message->isReacted());
        $this->assertFalse($message->isSystemMessage());
        $this->assertTrue($message->notSystemMessage());
        $this->assertFalse($message->isImage());
        $this->assertFalse($message->isDocument());
        $this->assertFalse($message->hasTemporaryId());
    }

    /** @test */
    public function it_cast_attributes()
    {
        $message = Message::factory()->for(
            Thread::factory()->create()
        )->owner($this->tippin)->trashed()->create();

        $this->assertInstanceOf(Carbon::class, $message->created_at);
        $this->assertInstanceOf(Carbon::class, $message->updated_at);
        $this->assertInstanceOf(Carbon::class, $message->deleted_at);
        $this->assertSame(0, $message->type);
        $this->assertFalse($message->edited);
        $this->assertFalse($message->reacted);
        $this->assertTrue($message->embeds);
    }

    /** @test */
    public function it_sets_temporary_id()
    {
        $message = Message::factory()->for(
            Thread::factory()->create()
        )->owner($this->tippin)->create();
        $message->setTemporaryId('1234');

        $this->assertTrue($message->hasTemporaryId());
        $this->assertSame('1234', $message->temporaryId());
    }

    /** @test */
    public function it_has_relations()
    {
        $thread = Thread::factory()->create();
        $reply = Message::factory()->for($thread)->owner($this->tippin)->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->reply($reply->id)->create();

        $this->assertSame($this->tippin->getKey(), $message->owner->getKey());
        $this->assertSame($thread->id, $message->thread->id);
        $this->assertInstanceOf(Thread::class, $message->thread);
        $this->assertInstanceOf(MessengerProvider::class, $message->owner);
        $this->assertInstanceOf(Collection::class, $message->edits);
        $this->assertInstanceOf(Collection::class, $message->reactions);
        $this->assertInstanceOf(Message::class, $message->replyTo);
    }

    /** @test */
    public function it_is_owned_by_current_provider()
    {
        Messenger::setProvider($this->tippin);
        $message = Message::factory()->for(
            Thread::factory()->create()
        )->owner($this->tippin)->create();

        $this->assertTrue($message->isOwnedByCurrentProvider());
    }

    /** @test */
    public function it_is_not_owned_by_current_provider()
    {
        Messenger::setProvider($this->doe);
        $message = Message::factory()->for(
            Thread::factory()->create()
        )->owner($this->tippin)->create();

        $this->assertFalse($message->isOwnedByCurrentProvider());
    }

    /** @test */
    public function it_has_private_owner_channel()
    {
        $message = Message::factory()->for(
            Thread::factory()->create()
        )->owner($this->tippin)->create();

        $this->assertSame('user.'.$this->tippin->getKey(), $message->getOwnerPrivateChannel());
    }

    /** @test */
    public function it_has_reply_message_cache_key()
    {
        $thread = Thread::factory()->create();
        $reply = Message::factory()->for($thread)->owner($this->tippin)->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->reply($reply->id)->create();

        $this->assertSame(Message::getReplyMessageCacheKey($message->reply_to_id), "reply:message:$reply->id");
        $this->assertSame(Message::getReplyMessageCacheKey('1234'), 'reply:message:1234');
    }

    /** @test */
    public function it_caches_reply_message()
    {
        $thread = Thread::factory()->create();
        $reply = Message::factory()->for($thread)->owner($this->tippin)->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->reply($reply->id)->create();
        $cache = Cache::spy();
        $cache->shouldReceive('remember')->andReturn($reply);

        $replyMessage = $message->getReplyMessage();

        $this->assertInstanceOf(Message::class, $replyMessage);
        $this->assertSame($reply->id, $replyMessage->id);
        $cache->shouldHaveReceived('remember');
        $this->assertNull($reply->getReplyMessage());
    }

    /** @test */
    public function owner_returns_ghost_if_not_found()
    {
        $message = Message::factory()->for(
            Thread::factory()->create()
        )->create([
            'owner_id' => 404,
            'owner_type' => $this->tippin->getMorphClass(),
        ]);

        $this->assertInstanceOf(GhostUser::class, $message->owner);
    }

    /** @test */
    public function it_has_storage_disk()
    {
        $message = Message::factory()->for(
            Thread::factory()->create()
        )->owner($this->tippin)->create();

        $this->assertSame('messenger', $message->getStorageDisk());
    }

    /** @test */
    public function it_has_storage_directory()
    {
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        $this->assertSame("threads/$thread->id", $message->getStorageDirectory());
    }

    /** @test */
    public function it_is_not_from_bot()
    {
        $message = Message::factory()->for(
            Thread::factory()->create()
        )->owner($this->tippin)->create();

        $this->assertFalse($message->isFromBot());
    }

    /** @test */
    public function it_is_from_bot()
    {
        $thread = Thread::factory()->group()->create();
        $bot = Bot::factory()->for($thread)->owner($this->tippin)->create();
        $message = Message::factory()->for($thread)->owner($bot)->create();

        $this->assertTrue($message->isFromBot());
    }

    /** @test */
    public function it_is_edited()
    {
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->edited()->create();

        $this->assertTrue($message->isEdited());
        $this->assertSame("/api/messenger/threads/$thread->id/messages/$message->id/history", $message->getEditHistoryRoute());
    }

    /** @test */
    public function it_has_reply_to_message()
    {
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();
        $reply = Message::factory()->for($thread)->owner($this->tippin)->create([
            'reply_to_id' => $message->id,
        ]);

        $this->assertNull($message->replyTo);
        $this->assertInstanceOf(Message::class, $reply->replyTo);
        $this->assertSame($message->id, $reply->replyTo->id);
    }

    /** @test */
    public function it_doesnt_have_reply_when_reply_message_deleted()
    {
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->trashed()->create();
        $reply = Message::factory()->for($thread)->owner($this->tippin)->create([
            'reply_to_id' => $message->id,
        ]);

        $this->assertNull($reply->replyTo);
    }

    /** @test */
    public function it_has_reactions()
    {
        $message = Message::factory()->for(
            Thread::factory()->create()
        )->owner($this->tippin)->reacted()->create();
        MessageReaction::factory()->for($message)->owner($this->tippin)->create();

        $this->assertCount(1, $message->reactions()->get());
        $this->assertTrue($message->isReacted());
    }

    /** @test */
    public function image_message()
    {
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->image()->create();

        $this->assertTrue($message->isImage());
        $this->assertFalse($message->isSystemMessage());
        $this->assertTrue($message->notSystemMessage());
        $this->assertFalse($message->isDocument());
        $this->assertFalse($message->isText());
        $this->assertFalse($message->isAudio());
        $this->assertFalse($message->isVideo());
        $this->assertSame(1, Message::image()->count());
        $this->assertSame('IMAGE_MESSAGE', $message->getTypeVerbose());
        $this->assertSame("threads/$thread->id/images/picture.jpg", $message->getImagePath());
        $this->assertSame("/messenger/assets/threads/$thread->id/gallery/$message->id/sm/picture.jpg", $message->getImageViewRoute());

        Messenger::shouldUseAbsoluteRoutes(true);

        $this->assertSame("http://messenger.test/messenger/assets/threads/$thread->id/gallery/$message->id/sm/picture.jpg", $message->getImageViewRoute());
    }

    /** @test */
    public function document_message()
    {
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->document()->create();

        $this->assertTrue($message->isDocument());
        $this->assertFalse($message->isSystemMessage());
        $this->assertTrue($message->notSystemMessage());
        $this->assertFalse($message->isImage());
        $this->assertFalse($message->isText());
        $this->assertFalse($message->isAudio());
        $this->assertFalse($message->isVideo());
        $this->assertSame(1, Message::document()->count());
        $this->assertSame('DOCUMENT_MESSAGE', $message->getTypeVerbose());
        $this->assertSame("threads/$thread->id/documents/document.pdf", $message->getDocumentPath());
        $this->assertSame("/messenger/assets/threads/$thread->id/files/$message->id/document.pdf", $message->getDocumentDownloadRoute());

        Messenger::shouldUseAbsoluteRoutes(true);

        $this->assertSame("http://messenger.test/messenger/assets/threads/$thread->id/files/$message->id/document.pdf", $message->getDocumentDownloadRoute());
    }

    /** @test */
    public function audio_message()
    {
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->audio()->create();

        $this->assertTrue($message->isAudio());
        $this->assertFalse($message->isDocument());
        $this->assertFalse($message->isSystemMessage());
        $this->assertTrue($message->notSystemMessage());
        $this->assertFalse($message->isImage());
        $this->assertFalse($message->isText());
        $this->assertFalse($message->isVideo());
        $this->assertSame(1, Message::audio()->count());
        $this->assertSame('AUDIO_MESSAGE', $message->getTypeVerbose());
        $this->assertSame("threads/$thread->id/audio/sound.mp3", $message->getAudioPath());
        $this->assertSame("/messenger/assets/threads/$thread->id/audio/$message->id/sound.mp3", $message->getAudioDownloadRoute());

        Messenger::shouldUseAbsoluteRoutes(true);

        $this->assertSame("http://messenger.test/messenger/assets/threads/$thread->id/audio/$message->id/sound.mp3", $message->getAudioDownloadRoute());
    }

    /** @test */
    public function video_message()
    {
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->video()->create();

        $this->assertTrue($message->isVideo());
        $this->assertFalse($message->isAudio());
        $this->assertFalse($message->isDocument());
        $this->assertFalse($message->isSystemMessage());
        $this->assertTrue($message->notSystemMessage());
        $this->assertFalse($message->isImage());
        $this->assertFalse($message->isText());
        $this->assertSame(1, Message::video()->count());
        $this->assertSame('VIDEO_MESSAGE', $message->getTypeVerbose());
        $this->assertSame("threads/$thread->id/videos/video.mov", $message->getVideoPath());
        $this->assertSame("/messenger/assets/threads/$thread->id/videos/$message->id/video.mov", $message->getVideoDownloadRoute());

        Messenger::shouldUseAbsoluteRoutes(true);

        $this->assertSame("http://messenger.test/messenger/assets/threads/$thread->id/videos/$message->id/video.mov", $message->getVideoDownloadRoute());
    }

    /** @test */
    public function system_message()
    {
        $message = Message::factory()->for(
            Thread::factory()->create()
        )->owner($this->tippin)->system(Message::GROUP_CREATED)->create();

        $this->assertTrue($message->isSystemMessage());
        $this->assertFalse($message->notSystemMessage());
        $this->assertFalse($message->isDocument());
        $this->assertFalse($message->isImage());
        $this->assertFalse($message->isText());
        $this->assertFalse($message->isAudio());
        $this->assertSame(1, Message::system()->count());
        $this->assertSame('GROUP_CREATED', $message->getTypeVerbose());
    }
}
