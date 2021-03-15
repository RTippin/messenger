<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\Messages\PurgeAudioMessages;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Tests\FeatureTestCase;

class PurgeAudioMessagesTest extends FeatureTestCase
{
    private Message $audio1;
    private Message $audio2;
    private string $disk;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();
        $group = $this->createGroupThread($tippin);
        $this->disk = Messenger::getThreadStorage('disk');
        Storage::fake($this->disk);
        $this->audio1 = $group->messages()->create([
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
            'type' => 3,
            'body' => 'test.mp3',
        ]);
        UploadedFile::fake()
            ->create('test.mp3', 500, 'audio/mpeg')
            ->storeAs($group->getStorageDirectory().'/audio', 'test.mp3', [
                'disk' => $this->disk,
            ]);
        $this->audio2 = $group->messages()->create([
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
            'type' => 3,
            'body' => 'foo.mp3',
        ]);
        UploadedFile::fake()
            ->create('foo.mp3', 500, 'audio/mpeg')
            ->storeAs($group->getStorageDirectory().'/audio', 'foo.mp3', [
                'disk' => $this->disk,
            ]);
    }

    /** @test */
    public function it_removes_messages()
    {
        app(PurgeAudioMessages::class)->execute(Message::audio()->get());

        $this->assertDatabaseMissing('messages', [
            'id' => $this->audio1->id,
        ]);
        $this->assertDatabaseMissing('messages', [
            'id' => $this->audio2->id,
        ]);
    }

    /** @test */
    public function it_removes_audio_from_disk()
    {
        app(PurgeAudioMessages::class)->execute(Message::audio()->get());

        Storage::disk($this->disk)->assertMissing($this->audio1->getAudioPath());
        Storage::disk($this->disk)->assertMissing($this->audio2->getAudioPath());
    }
}
