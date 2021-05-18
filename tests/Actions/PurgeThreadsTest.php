<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\Threads\PurgeThreads;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PurgeThreadsTest extends FeatureTestCase
{
    /** @test */
    public function it_removes_threads_and_participants()
    {
        $this->createPrivateThread($this->tippin, $this->doe);
        $this->createGroupThread($this->tippin);

        app(PurgeThreads::class)->execute(Thread::all());

        $this->assertDatabaseCount('participants', 0);
        $this->assertDatabaseCount('threads', 0);
    }

    /** @test */
    public function it_removes_files_and_directories_from_disk()
    {
        $thread = Thread::factory()->create();
        UploadedFile::fake()
            ->image('avatar.jpg')
            ->storeAs($thread->getAvatarDirectory(), 'avatar.jpg', [
                'disk' => 'messenger',
            ]);
        UploadedFile::fake()
            ->image('avatar.jpg')
            ->storeAs($thread->getAvatarDirectory(), 'avatar.jpg', [
                'disk' => 'messenger',
            ]);

        app(PurgeThreads::class)->execute(Thread::all());

        Storage::disk('messenger')->assertMissing($thread->getStorageDirectory());
    }
}
