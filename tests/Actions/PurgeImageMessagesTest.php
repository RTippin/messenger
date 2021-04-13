<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\Messages\PurgeImageMessages;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Tests\FeatureTestCase;

class PurgeImageMessagesTest extends FeatureTestCase
{
    private Message $image1;
    private Message $image2;
    private string $disk;

    protected function setUp(): void
    {
        parent::setUp();

        $group = $this->createGroupThread($this->tippin);
        $this->disk = Messenger::getThreadStorage('disk');
        Storage::fake($this->disk);
        $this->image1 = Message::factory()->for($group)->owner($this->tippin)->image()->create();
        UploadedFile::fake()->image('picture.jpg')
            ->storeAs($group->getStorageDirectory().'/images', 'picture.jpg', [
                'disk' => $this->disk,
            ]);
        $this->image2 = Message::factory()->for($group)->owner($this->tippin)->image()->create(['body' => 'foo.jpg']);
        UploadedFile::fake()->image('foo.jpg')
            ->storeAs($group->getStorageDirectory().'/images', 'foo.jpg', [
                'disk' => $this->disk,
            ]);
    }

    /** @test */
    public function it_removes_messages()
    {
        app(PurgeImageMessages::class)->execute(Message::image()->get());

        $this->assertDatabaseMissing('messages', [
            'id' => $this->image1->id,
        ]);
        $this->assertDatabaseMissing('messages', [
            'id' => $this->image2->id,
        ]);
    }

    /** @test */
    public function it_removes_images_from_disk()
    {
        app(PurgeImageMessages::class)->execute(Message::image()->get());

        Storage::disk($this->disk)->assertMissing($this->image1->getImagePath());
        Storage::disk($this->disk)->assertMissing($this->image2->getImagePath());
    }
}
