<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\StoreGroupThread;
use RTippin\Messenger\Broadcasting\NewThreadBroadcast;
use RTippin\Messenger\Events\NewThreadEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreGroupThreadTest extends FeatureTestCase
{
    /** @test */
    public function store_group_without_extra_participants()
    {
        $tippin = $this->userTippin();

        Messenger::setProvider($tippin);

        app(StoreGroupThread::class)->withoutDispatches()->execute([
            'subject' => 'Test Group',
        ]);

        $this->assertDatabaseCount('participants', 1);

        $this->assertDatabaseCount('threads', 1);

        $this->assertDatabaseHas('threads', [
            'subject' => 'Test Group',
        ]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $tippin->getKey(),
            'owner_type' => get_class($tippin),
            'admin' => true,
        ]);
    }

    /** @test */
    public function store_group_without_extra_participants_fires_one_event()
    {
        Event::fake([
            NewThreadEvent::class,
            NewThreadBroadcast::class,
        ]);

        $tippin = $this->userTippin();

        Messenger::setProvider($tippin);

        app(StoreGroupThread::class)->execute([
            'subject' => 'Test Group',
        ]);

        Event::assertDispatched(function (NewThreadEvent $event) use ($tippin) {
            $this->assertSame($tippin->getKey(), $event->provider->getKey());
            $this->assertSame('Test Group', $event->thread->subject);

            return true;
        });

        Event::assertNotDispatched(NewThreadBroadcast::class);
    }
}
