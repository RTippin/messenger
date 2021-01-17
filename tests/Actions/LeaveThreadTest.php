<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\LeaveThread;
use RTippin\Messenger\Broadcasting\ThreadLeftBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\ThreadLeftEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class LeaveThreadTest extends FeatureTestCase
{
    private Thread $group;

    private MessengerProvider $tippin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->group = $this->createGroupThread($this->tippin);
    }

    /** @test */
    public function leave_thread_soft_deletes_participant()
    {
        Messenger::setProvider($this->tippin);

        $participant = $this->group->participants()->first();

        app(LeaveThread::class)->withoutDispatches()->execute($this->group);

        $this->assertSoftDeleted('participants', [
            'id' => $participant->id,
        ]);
    }

    /** @test */
    public function leave_thread_fires_events()
    {
        Event::fake([
            ThreadLeftBroadcast::class,
            ThreadLeftEvent::class,
        ]);

        Messenger::setProvider($this->tippin);

        $participant = $this->group->participants()->first();

        app(LeaveThread::class)->execute($this->group);

        Event::assertDispatched(function (ThreadLeftBroadcast $event) {
            $this->assertContains('private-user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (ThreadLeftEvent $event) use ($participant) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($this->group->id, $event->thread->id);
            $this->assertEquals($participant->id, $event->participant->id);

            return true;
        });
    }
}
