<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\Messages\StoreAudioMessage;
use RTippin\Messenger\Broadcasting\NewMessageBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\NewMessageEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreAudioMessageTest extends FeatureTestCase
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
    public function it_throws_exception_if_disabled()
    {
        Messenger::setMessageAudioUpload(false);

        $this->expectException(FeatureDisabledException::class);
        $this->expectExceptionMessage('Audio messages are currently disabled.');

        app(StoreAudioMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
            ]
        );
    }

    /** @test */
    public function it_stores_audio()
    {
        app(StoreAudioMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
            ]
        );

        $this->assertDatabaseHas('messages', [
            'thread_id' => $this->private->id,
            'type' => 3,
        ]);
    }

    /** @test */
    public function it_stores_audio_file()
    {
        app(StoreAudioMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
            ]
        );

        Storage::disk($this->disk)->assertExists(Message::audio()->first()->getAudioPath());
    }

    /** @test */
    public function it_sets_temporary_id_on_message()
    {
        $action = app(StoreAudioMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
                'temporary_id' => '123-456-789',
            ]
        );

        $this->assertSame('123-456-789', $action->getMessage()->temporaryId());
    }

    /** @test */
    public function it_can_reply_to_existing_message()
    {
        $message = $this->createMessage($this->private, $this->tippin);

        app(StoreAudioMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
                'temporary_id' => '123-456-789',
                'reply_to_id' => $message->id,
            ]
        );

        $this->assertDatabaseHas('messages', [
            'thread_id' => $this->private->id,
            'type' => 3,
            'reply_to_id' => $message->id,
        ]);
    }

    /** @test */
    public function it_updates_thread_and_participant()
    {
        $updated = now()->addMinutes(5)->format('Y-m-d H:i:s.u');
        Carbon::setTestNow($updated);

        app(StoreAudioMessage::class)->withoutDispatches()->execute(
            $this->private,
            [
                'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
            ]
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
    public function it_fires_events()
    {
        Event::fake([
            NewMessageBroadcast::class,
            NewMessageEvent::class,
        ]);

        app(StoreAudioMessage::class)->execute(
            $this->private,
            [
                'audio' => UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'),
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
