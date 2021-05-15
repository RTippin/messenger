<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RTippin\Messenger\Actions\Threads\PurgeThreads;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class PurgeThreadsTest extends FeatureTestCase
{
    private Thread $private;
    private Thread $group;
    private string $disk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->private = $this->createPrivateThread($this->tippin, $this->doe);
        $this->group = $this->createGroupThread($this->tippin);
        $this->disk = Messenger::getThreadStorage('disk');
        Storage::fake($this->disk);
    }

    /** @test */
    public function it_removes_threads_and_participants()
    {
        app(PurgeThreads::class)->execute(Thread::all());

        $this->assertDatabaseMissing('participants', [
            'thread_id' => $this->private->id,
        ]);
        $this->assertDatabaseMissing('participants', [
            'thread_id' => $this->group->id,
        ]);
        $this->assertDatabaseMissing('threads', [
            'id' => $this->private->id,
        ]);
        $this->assertDatabaseMissing('threads', [
            'id' => $this->group->id,
        ]);
    }

    /** @test */
    public function it_removes_files_and_directories_from_disk()
    {
        UploadedFile::fake()
            ->image('avatar.jpg')
            ->storeAs($this->private->getAvatarDirectory(), 'avatar.jpg', [
                'disk' => $this->disk,
            ]);

        UploadedFile::fake()
            ->image('avatar.jpg')
            ->storeAs($this->group->getAvatarDirectory(), 'avatar.jpg', [
                'disk' => $this->group->getStorageDisk(),
            ]);

        app(PurgeThreads::class)->execute(Thread::all());

        Storage::disk($this->disk)->assertMissing($this->private->getStorageDirectory());
        Storage::disk($this->disk)->assertMissing($this->group->getStorageDirectory());
    }
}
