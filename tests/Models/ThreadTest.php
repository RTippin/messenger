<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class ThreadTest extends FeatureTestCase
{
    private MessengerProvider $tippin;

    private MessengerProvider $doe;

    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->doe = $this->userDoe();

        $this->group = $this->createGroupThread($this->tippin, $this->doe);
    }

    /** @test */
    public function threads_exists()
    {
        $this->assertDatabaseCount('threads', 1);
        $this->assertDatabaseHas('threads', [
            'id' => $this->group->id,
        ]);
        $this->assertInstanceOf(Thread::class, $this->group);
        $this->assertSame(0, Thread::private()->count());
        $this->assertSame(1, Thread::group()->count());
    }

    /** @test */
    public function thread_attributes_casted()
    {
        $this->group->update([
            'deleted_at' => now(),
        ]);

        $this->assertInstanceOf(Carbon::class, $this->group->created_at);
        $this->assertInstanceOf(Carbon::class, $this->group->updated_at);
        $this->assertInstanceOf(Carbon::class, $this->group->deleted_at);
        $this->assertTrue($this->group->add_participants);
        $this->assertTrue($this->group->invitations);
        $this->assertTrue($this->group->calling);
        $this->assertTrue($this->group->messaging);
        $this->assertTrue($this->group->knocks);
        $this->assertSame(2, $this->group->type);
        $this->assertSame('First Test Group', $this->group->subject);
    }

    /** @test */
    public function thread_has_relations()
    {
        $this->assertInstanceOf(Collection::class, $this->group->participants);
        $this->assertInstanceOf(Collection::class, $this->group->messages);
        $this->assertInstanceOf(Collection::class, $this->group->calls);
        $this->assertInstanceOf(Collection::class, $this->group->invites);
    }

    /** @test */
    public function thread_type_verbose()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        $this->assertSame('PRIVATE', $private->getTypeVerbose());
        $this->assertSame('GROUP', $this->group->getTypeVerbose());
    }

    /** @test */
    public function thread_storage()
    {
        $this->group->update([
            'image' => 'test.png',
        ]);

        $this->assertSame('messenger', $this->group->getStorageDisk());
        $this->assertSame("threads/{$this->group->id}", $this->group->getStorageDirectory());
        $this->assertSame("threads/{$this->group->id}/avatar/test.png", $this->group->getAvatarPath());
    }

    /** @test */
    public function thread_does_not_have_current_participant_when_provider_not_set()
    {
        $this->assertNull($this->group->currentParticipant());
        $this->assertFalse($this->group->hasCurrentProvider());
    }

    /** @test */
    public function thread_does_not_have_current_participant_when_not_in_thread()
    {
        Messenger::setProvider($this->companyDevelopers());

        $this->assertNull($this->group->currentParticipant());
        $this->assertFalse($this->group->hasCurrentProvider());
    }

    /** @test */
    public function thread_has_current_participant()
    {
        Messenger::setProvider($this->tippin);

        $this->assertInstanceOf(Participant::class, $this->group->currentParticipant());
        $this->assertTrue($this->group->hasCurrentProvider());
        $this->assertEquals($this->tippin->getKey(), $this->group->currentParticipant()->owner_id);
    }

    /** @test */
    public function thread_recipient_returns_ghost_participant_when_group_thread()
    {
        Messenger::setProvider($this->tippin);

        $this->assertInstanceOf(Participant::class, $this->group->recipient());
        $this->assertInstanceOf(GhostUser::class, $this->group->recipient()->owner);
        $this->assertSame($this->group->id, $this->group->recipient()->thread_id);
        $this->assertNull($this->group->recipient()->owner_id);
    }

    /** @test */
    public function thread_recipient_returns_ghost_participant_when_not_in_thread()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        Messenger::setProvider($this->companyDevelopers());

        $this->assertInstanceOf(Participant::class, $private->recipient());
        $this->assertInstanceOf(GhostUser::class, $private->recipient()->owner);
        $this->assertSame($private->id, $private->recipient()->thread_id);
        $this->assertNull($private->recipient()->owner_id);
    }

    /** @test */
    public function thread_recipient_returns_other_participant_in_private_thread()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        Messenger::setProvider($this->tippin);

        $this->assertInstanceOf(Participant::class, $private->recipient());
        $this->assertInstanceOf(MessengerProvider::class, $private->recipient()->owner);
        $this->assertSame($private->id, $private->recipient()->thread_id);
        $this->assertEquals($this->doe->getKey(), $private->recipient()->owner_id);
    }

    /** @test */
    public function thread_recipient_returns_ghost_participant_when_invalid_recipient()
    {
        $private = $this->createPrivateThread($this->tippin, $this->companyDevelopers());

        Messenger::setMessengerProviders(['user' => $this->getBaseProvidersConfig()['user']]);

        Messenger::setProvider($this->tippin);

        $this->assertInstanceOf(Participant::class, $private->recipient());
        $this->assertInstanceOf(GhostUser::class, $private->recipient()->owner);
        $this->assertSame($private->id, $private->recipient()->thread_id);
        $this->assertNull($private->recipient()->owner_id);
    }

    /** @test */
    public function thread_has_name_depending_on_type()
    {
        $private = $this->createPrivateThread($this->tippin, $this->doe);

        Messenger::setProvider($this->tippin);

        $this->assertSame('First Test Group', $this->group->name());
        $this->assertSame('John Doe', $private->name());
    }
}
