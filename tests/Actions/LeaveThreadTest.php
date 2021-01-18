<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\LeaveThread;
use RTippin\Messenger\Broadcasting\ThreadLeftBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\ThreadLeftEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class LeaveThreadTest extends FeatureTestCase
{
    private Thread $group;

    private Participant $participant;

    private MessengerProvider $tippin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->group = $this->createGroupThread($this->tippin);

        $this->participant = $this->group->participants()->first();

        Messenger::setProvider($this->tippin);
    }

    /** @test */
    public function leave_thread_soft_deletes_participant()
    {
        app(LeaveThread::class)->withoutDispatches()->execute($this->group);

        $this->assertSoftDeleted('participants', [
            'id' => $this->participant->id,
        ]);
    }

    /** @test */
    public function leave_thread_fires_events()
    {
        Event::fake([
            ThreadLeftBroadcast::class,
            ThreadLeftEvent::class,
        ]);

        app(LeaveThread::class)->execute($this->group);

        Event::assertDispatched(function (ThreadLeftBroadcast $event) {
            $this->assertContains('private-user.'.$this->tippin->getKey(), $event->broadcastOn());
            $this->assertSame($this->group->id, $event->broadcastWith()['thread_id']);

            return true;
        });

        Event::assertDispatched(function (ThreadLeftEvent $event) {
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertSame($this->group->id, $event->thread->id);
            $this->assertEquals($this->participant->id, $event->participant->id);

            return true;
        });
    }
}
