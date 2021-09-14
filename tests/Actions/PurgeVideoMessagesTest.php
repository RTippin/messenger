<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\Messages\PurgeVideoMessages;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PurgeVideoMessagesTest extends FeatureTestCase
{
    /** @test */
    public function it_removes_messages()
    {
        $thread = Thread::factory()->create();
        $video1 = Message::factory()->for($thread)->owner($this->tippin)->video()->create();
        $video2 = Message::factory()->for($thread)->owner($this->tippin)->video()->create();

        app(PurgeVideoMessages::class)->execute(Message::video()->get());

        $this->assertDatabaseMissing('messages', [
            'id' => $video1->id,
        ]);
        $this->assertDatabaseMissing('messages', [
            'id' => $video2->id,
        ]);
    }

    /** @test */
    public function it_removes_video_from_disk()
    {
        $thread = Thread::factory()->create();
        $video1 = Message::factory()->for($thread)->owner($this->tippin)->video()->create(['body' => 'test.mov']);
        $video2 = Message::factory()->for($thread)->owner($this->tippin)->video()->create(['body' => 'foo.mov']);
        UploadedFile::fake()
            ->create('test.mov', 500, 'video/quicktime')
            ->storeAs($thread->getVideoDirectory(), 'test.mov', [
                'disk' => 'messenger',
            ]);
        UploadedFile::fake()
            ->create('foo.mov', 500, 'video/quicktime')
            ->storeAs($thread->getVideoDirectory(), 'foo.mov', [
                'disk' => 'messenger',
            ]);

        app(PurgeVideoMessages::class)->execute(Message::video()->get());

        Storage::disk('messenger')->assertMissing($video1->getVideoPath());
        Storage::disk('messenger')->assertMissing($video2->getVideoPath());
    }
}
