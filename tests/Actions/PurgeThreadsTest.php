<?php

namespace RTippin\Messenger\Tests\Actions;

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
    public function purge_threads_removes_from_database()
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
}
