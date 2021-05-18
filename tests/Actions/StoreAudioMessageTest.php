<?php

namespace RTippin\Messenger\Tests\Actions;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Messages\StoreAudioMessage;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\FileService;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreAudioMessageTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function it_throws_exception_if_disabled()
    {
        Messenger::setMessageAudioUpload(false);
        $thread = Thread::factory()->create();

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Audio messages are currently disabled.');

        app(StoreAudioMessage::class)->execute($thread, [
            'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
        ]);
    }

    /** @test */
    public function it_throws_exception_if_transaction_fails_and_removes_uploaded_audio()
    {
        $thread = Thread::factory()->create();
        DB::shouldReceive('transaction')->andThrow(new Exception('DB Error'));
        $this->mock(FileService::class)->shouldReceive([
            'setType' => Mockery::self(),
            'setDisk' => Mockery::self(),
            'setDirectory' => Mockery::self(),
            'upload' => 'test.mp3',
            'destroy' => true,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('DB Error');

        app(StoreAudioMessage::class)->execute($thread, [
            'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
        ]);
    }

    /** @test */
    public function it_stores_audio_message()
    {
        $thread = Thread::factory()->create();

        app(StoreAudioMessage::class)->execute($thread, [
            'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
        ]);

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'type' => 3,
        ]);
    }

    /** @test */
    public function it_stores_audio_file()
    {
        $thread = Thread::factory()->create();

        app(StoreAudioMessage::class)->execute($thread, [
            'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
        ]);

        Storage::disk('messenger')->assertExists(Message::audio()->first()->getAudioPath());
    }

    /** @test */
    public function it_sets_temporary_id_on_message()
    {
        $thread = Thread::factory()->create();

        $action = app(StoreAudioMessage::class)->execute($thread, [
            'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
            'temporary_id' => '123-456-789',
        ]);

        $this->assertSame('123-456-789', $action->getMessage()->temporaryId());
    }

    /** @test */
    public function it_can_add_extra_data_on_message()
    {
        $thread = Thread::factory()->create();

        app(StoreAudioMessage::class)->execute($thread, [
            'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
            'extra' => ['test' => true],
        ]);

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'type' => 3,
            'extra' => '{"test":true}',
        ]);
    }

    /** @test */
    public function it_can_reply_to_existing_message()
    {
        $thread = Thread::factory()->create();
        $message = Message::factory()->for($thread)->owner($this->tippin)->create();

        app(StoreAudioMessage::class)->execute($thread, [
            'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
            'reply_to_id' => $message->id,
        ]);

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'type' => 3,
            'reply_to_id' => $message->id,
        ]);
    }

    /** @test */
    public function it_updates_thread_and_participant()
    {
        $thread = Thread::factory()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->create();
        $updated = now()->addMinutes(5)->format('Y-m-d H:i:s.u');
        Carbon::setTestNow($updated);

        app(StoreAudioMessage::class)->execute($thread, [
            'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
        ]);

        $this->assertDatabaseHas('threads', [
            'id' => $thread->id,
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
        BaseMessengerAction::enableEvents();
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        app(StoreAudioMessage::class)->execute($thread, [
            'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
        ]);

        Event::assertDispatched(function (NewMessageBroadcast $event) use ($thread) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertContains('private-messenger.user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($thread->id, $event->broadcastWith()['thread_id']);

            return true;
        });
        Event::assertDispatched(function (NewMessageEvent $event) use ($thread) {
            return $thread->id === $event->message->thread_id;
        });
    }
}
