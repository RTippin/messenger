<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Event;
use RTippin\Messenger\Actions\Threads\ThreadApproval;
use RTippin\Messenger\Broadcasting\ThreadApprovalBroadcast;
use RTippin\Messenger\Events\ThreadApprovalEvent;
use RTippin\Messenger\Exceptions\ThreadApprovalException;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class ThreadApprovalTest extends FeatureTestCase
{
    private Thread $private;

    protected function setUp(): void
    {
        parent::setUp();

        $this->private = $this->createPrivateThread($this->tippin, $this->doe, true);
    }

    /** @test */
    public function it_approves_and_updates_participant()
    {
        Messenger::setProvider($this->tippin);

        app(ThreadApproval::class)->withoutDispatches()->execute(
            $this->private,
            true
        );

        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->tippin->getKey(),
            'owner_type' => get_class($this->tippin),
            'pending' => false,
        ]);
    }

    /** @test */
    public function it_denies_and_soft_deletes_thread()
    {
        Messenger::setProvider($this->tippin);

        app(ThreadApproval::class)->withoutDispatches()->execute(
            $this->private,
            false
        );

        $this->assertSoftDeleted('threads', [
            'id' => $this->private->id,
        ]);
    }

    /** @test */
    public function it_throws_exception_if_not_receiver_approving()
    {
        $this->expectException(ThreadApprovalException::class);
        $this->expectExceptionMessage('Not authorized to approve that conversation.');

        Messenger::setProvider($this->doe);

        app(ThreadApproval::class)->withoutDispatches()->execute(
            $this->private,
            true
        );
    }

    /** @test */
    public function it_throws_exception_if_not_pending()
    {
        $this->expectException(ThreadApprovalException::class);
        $this->expectExceptionMessage('That conversation is not pending.');

        Messenger::setProvider($this->tippin);
        $this->private->participants()
            ->where('pending', '=', true)
            ->first()
            ->update([
                'pending' => false,
            ]);

        app(ThreadApproval::class)->withoutDispatches()->execute(
            $this->private,
            true
        );
    }

    /** @test */
    public function it_throws_exception_if_group_thread()
    {
        $this->expectException(ThreadApprovalException::class);
        $this->expectExceptionMessage('Group threads do not have approvals.');

        Messenger::setProvider($this->tippin);
        $group = $this->createGroupThread($this->tippin);

        app(ThreadApproval::class)->withoutDispatches()->execute(
            $group,
            true
        );
    }

    /** @test */
    public function it_fires_approved_events()
    {
        Messenger::setProvider($this->tippin);
        Event::fake([
            ThreadApprovalBroadcast::class,
            ThreadApprovalEvent::class,
        ]);

        app(ThreadApproval::class)->execute(
            $this->private,
            true
        );

        Event::assertDispatched(function (ThreadApprovalBroadcast $event) {
            $this->assertContains('private-messenger.user.'.$this->doe->getKey(), $event->broadcastOn());
            $this->assertSame($this->private->id, $event->broadcastWith()['thread']['id']);
            $this->assertTrue($event->broadcastWith()['thread']['approved']);

            return true;
        });
        Event::assertDispatched(function (ThreadApprovalEvent $event) {
            $this->assertSame($this->private->id, $event->thread->id);
            $this->assertSame($this->tippin->getKey(), $event->provider->getKey());
            $this->assertTrue($event->approved);

            return true;
        });
    }

    /** @test */
    public function it_fires_denied_events()
    {
        Messenger::setProvider($this->tippin);
        Event::fake([
            ThreadApprovalBroadcast::class,
            ThreadApprovalEvent::class,
        ]);

        app(ThreadApproval::class)->execute(
            $this->private,
            false
        );

        Event::assertDispatched(function (ThreadApprovalBroadcast $event) {
            return $event->broadcastWith()['thread']['approved'] === false;
        });
        Event::assertDispatched(ThreadApprovalEvent::class);
    }
}
