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

        $tippin = $this->userTippin();

        $group = $this->createGroupThread($tippin);

        $this->disk = Messenger::getThreadStorage('disk');

        Storage::fake($this->disk);

        $this->image1 = $group->messages()->create([
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
            'type' => 1,
            'body' => 'picture.jpg',
        ]);

        UploadedFile::fake()->image('picture.jpg')
            ->storeAs($group->getStorageDirectory().'/images', 'picture.jpg', [
                'disk' => $this->disk,
            ]);

        $this->image2 = $group->messages()->create([
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
            'type' => 1,
            'body' => 'foo.jpg',
        ]);

        UploadedFile::fake()->image('foo.jpg')
            ->storeAs($group->getStorageDirectory().'/images', 'foo.jpg', [
                'disk' => $this->disk,
            ]);
    }

    /** @test */
    public function purge_images_removes_messages_from_database()
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
    public function purge_images_removes_stored_images()
    {
        app(PurgeImageMessages::class)->execute(Message::image()->get());

        Storage::disk($this->disk)->assertMissing($this->image1->getImagePath());

        Storage::disk($this->disk)->assertMissing($this->image2->getImagePath());
    }
}
