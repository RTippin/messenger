<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\Messages\PurgeImageMessages;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PurgeImageMessagesTest extends FeatureTestCase
{
    /** @test */
    public function it_removes_messages()
    {
        $thread = Thread::factory()->create();
        $image1 = Message::factory()->for($thread)->owner($this->tippin)->image()->create();
        $image2 = Message::factory()->for($thread)->owner($this->tippin)->image()->create();

        app(PurgeImageMessages::class)->execute(Message::image()->get());

        $this->assertDatabaseMissing('messages', [
            'id' => $image1->id,
        ]);
        $this->assertDatabaseMissing('messages', [
            'id' => $image2->id,
        ]);
    }

    /** @test */
    public function it_removes_images_from_disk()
    {
        $thread = Thread::factory()->create();
        $image1 = Message::factory()->for($thread)->owner($this->tippin)->image()->create();
        $image2 = Message::factory()->for($thread)->owner($this->tippin)->image()->create(['body' => 'foo.jpg']);
        UploadedFile::fake()->image('picture.jpg')
            ->storeAs($thread->getImagesDirectory(), 'picture.jpg', [
                'disk' => 'messenger',
            ]);
        UploadedFile::fake()->image('foo.jpg')
            ->storeAs($thread->getImagesDirectory(), 'foo.jpg', [
                'disk' => 'messenger',
            ]);

        app(PurgeImageMessages::class)->execute(Message::image()->get());

        Storage::disk('messenger')->assertMissing($image1->getImagePath());
        Storage::disk('messenger')->assertMissing($image2->getImagePath());
    }
}
