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

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $this->private = $this->createPrivateThread($tippin, $this->userDoe());

        $this->group = $this->createGroupThread($tippin);

        Storage::fake(Messenger::getThreadStorage('disk'));
    }

    /** @test */
    public function purge_threads_removes_threads_from_database()
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
    public function purge_threads_removes_stored_files_and_directories()
    {
        UploadedFile::fake()
            ->image('avatar.jpg')
            ->storeAs($this->private->getStorageDirectory().'/avatar', 'avatar.jpg', [
                'disk' => $this->private->getStorageDisk(),
            ]);

        UploadedFile::fake()
            ->image('avatar.jpg')
            ->storeAs($this->group->getStorageDirectory().'/avatar', 'avatar.jpg', [
                'disk' => $this->group->getStorageDisk(),
            ]);

        app(PurgeThreads::class)->execute(Thread::all());

        Storage::disk($this->private->getStorageDisk())->assertMissing($this->private->getStorageDirectory());

        Storage::disk($this->group->getStorageDisk())->assertMissing($this->group->getStorageDirectory());
    }
}
