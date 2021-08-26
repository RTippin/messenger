<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Actions\Threads\ThreadApproval;
use RTippin\Messenger\Broadcasting\ThreadApprovalBroadcast;
use RTippin\Messenger\Events\ThreadApprovalEvent;
use RTippin\Messenger\Exceptions\ThreadApprovalException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\BroadcastLogger;
use RTippin\Messenger\Tests\FeatureTestCase;

class ThreadApprovalTest extends FeatureTestCase
{
    use BroadcastLogger;

    /** @test */
    public function it_approves_and_updates_participant()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->pending()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();

        app(ThreadApproval::class)->execute($thread, true);

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'pending' => false,
        ]);
    }

    /** @test */
    public function it_denies_and_soft_deletes_thread()
    {
        Messenger::setProvider($this->tippin);
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->pending()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();

        app(ThreadApproval::class)->execute($thread, false);

        $this->assertSoftDeleted('threads', [
            'id' => $thread->id,
        ]);
    }

    /** @test */
    public function it_throws_exception_if_not_receiver_approving()
    {
        Messenger::setProvider($this->doe);
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->pending()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();

        $this->expectException(ThreadApprovalException::class);
        $this->expectExceptionMessage('Not authorized to approve that conversation.');

        app(ThreadApproval::class)->execute($thread, true);
    }

    /** @test */
    public function it_throws_exception_if_not_pending()
    {
        Messenger::setProvider($this->tippin);
        $thread = $this->createPrivateThread($this->tippin, $this->doe);

        $this->expectException(ThreadApprovalException::class);
        $this->expectExceptionMessage('That conversation is not pending.');

        app(ThreadApproval::class)->execute($thread, true);
    }

    /** @test */
    public function it_throws_exception_if_group_thread()
    {
        Messenger::setProvider($this->tippin);

        $this->expectException(ThreadApprovalException::class);
        $this->expectExceptionMessage('Group threads do not have approvals.');

        app(ThreadApproval::class)->execute(Thread::factory()->group()->create(), true);
    }

    /** @test */
    public function it_fires_approved_events()
    {
        BaseMessengerAction::enableEvents();
        Messenger::setProvider($this->tippin);
        Event::fake([
            ThreadApprovalBroadcast::class,
            ThreadApprovalEvent::class,
        ]);
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->pending()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();

        app(ThreadApproval::class)->execute($thread, true);

        Event::assertDispatched(function (ThreadApprovalBroadcast $event) use ($thread) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($thread->id, $event->broadcastWith()['thread']['id']);
            $this->assertTrue($event->broadcastWith()['thread']['approved']);

            return true;
        });
        Event::assertDispatched(function (ThreadApprovalEvent $event) use ($thread) {
            $this->assertSame($thread->id, $event->thread->id);
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertTrue($event->approved);

            return true;
        });
        $this->logBroadcast(ThreadApprovalBroadcast::class, 'Thread approved.');
    }

    /** @test */
    public function it_fires_denied_events()
    {
        BaseMessengerAction::enableEvents();
        Messenger::setProvider($this->tippin);
        Event::fake([
            ThreadApprovalBroadcast::class,
            ThreadApprovalEvent::class,
        ]);
        $thread = Thread::factory()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->pending()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();

        app(ThreadApproval::class)->execute($thread, false);

        Event::assertDispatched(function (ThreadApprovalBroadcast $event) {
            return $event->broadcastWith()['thread']['approved'] === false;
        });
        Event::assertDispatched(ThreadApprovalEvent::class);
        $this->logBroadcast(ThreadApprovalBroadcast::class, 'Thread denied.');
    }
}
