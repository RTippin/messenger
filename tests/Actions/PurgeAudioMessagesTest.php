<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\Messages\PurgeAudioMessages;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PurgeAudioMessagesTest extends FeatureTestCase
{
    /** @test */
    public function it_removes_messages()
    {
        $thread = Thread::factory()->create();
        $audio1 = Message::factory()->for($thread)->owner($this->tippin)->audio()->create();
        $audio2 = Message::factory()->for($thread)->owner($this->tippin)->audio()->create();

        app(PurgeAudioMessages::class)->execute(Message::audio()->get());

        $this->assertDatabaseMissing('messages', [
            'id' => $audio1->id,
        ]);
        $this->assertDatabaseMissing('messages', [
            'id' => $audio2->id,
        ]);
    }

    /** @test */
    public function it_removes_audio_from_disk()
    {
        $thread = Thread::factory()->create();
        $audio1 = Message::factory()->for($thread)->owner($this->tippin)->audio()->create(['body' => 'test.mp3']);
        $audio2 = Message::factory()->for($thread)->owner($this->tippin)->audio()->create(['body' => 'foo.mp3']);
        UploadedFile::fake()
            ->create('test.mp3', 500, 'audio/mpeg')
            ->storeAs($thread->getAudioDirectory(), 'test.mp3', [
                'disk' => 'messenger',
            ]);
        UploadedFile::fake()
            ->create('foo.mp3', 500, 'audio/mpeg')
            ->storeAs($thread->getAudioDirectory(), 'foo.mp3', [
                'disk' => 'messenger',
            ]);

        app(PurgeAudioMessages::class)->execute(Message::audio()->get());

        Storage::disk('messenger')->assertMissing($audio1->getAudioPath());
        Storage::disk('messenger')->assertMissing($audio2->getAudioPath());
    }
}
