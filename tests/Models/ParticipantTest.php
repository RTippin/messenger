<?php

namespace RTippin\Messenger\Tests\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\GhostUser;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class ParticipantTest extends FeatureTestCase
{
    private MessengerProvider $tippin;

    private Thread $group;

    private Participant $admin;

    private Message $message;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();

        $this->group = $this->createGroupThread($this->tippin, $this->userDoe(), $this->companyDevelopers());

        $this->admin = $this->group->participants()->admins()->first();

        $this->message = $this->createMessage($this->group, $this->tippin);
    }

    /** @test */
    public function participants_exists()
    {
        $this->assertDatabaseCount('participants', 3);
        $this->assertDatabaseHas('participants', [
            'id' => $this->admin->id,
        ]);
        $this->assertInstanceOf(Participant::class, $this->admin);
        $this->assertSame(1, Participant::admins()->count());
        $this->assertSame(3, Participant::notMuted()->count());
        $this->assertSame(3, Participant::notPending()->count());
        $this->assertSame(3, Participant::validProviders()->count());
    }

    /** @test */
    public function participant_attributes_casted()
    {
        $this->admin->update([
            'last_read' => now(),
            'deleted_at' => now(),
        ]);

        $this->assertInstanceOf(Carbon::class, $this->admin->created_at);
        $this->assertInstanceOf(Carbon::class, $this->admin->updated_at);
        $this->assertInstanceOf(Carbon::class, $this->admin->deleted_at);
        $this->assertTrue($this->admin->admin);
        $this->assertTrue($this->admin->send_messages);
        $this->assertTrue($this->admin->send_knocks);
        $this->assertTrue($this->admin->start_calls);
        $this->assertTrue($this->admin->add_participants);
        $this->assertTrue($this->admin->manage_invites);
        $this->assertFalse($this->admin->muted);
        $this->assertFalse($this->admin->pending);
    }

    /** @test */
    public function participant_has_relations()
    {
        $this->assertSame($this->tippin->getKey(), $this->admin->owner->getKey());
        $this->assertSame($this->group->id, $this->admin->thread->id);
        $this->assertInstanceOf(Thread::class, $this->admin->thread);
        $this->assertInstanceOf(MessengerProvider::class, $this->admin->owner);
        $this->assertInstanceOf(Collection::class, $this->admin->messages);
    }

    /** @test */
    public function participant_owner_returns_ghost_when_not_found()
    {
        $this->admin->update([
            'owner_id' => 404,
        ]);

        $this->assertInstanceOf(GhostUser::class, $this->admin->owner);
    }

    /** @test */
    public function participant_has_last_seen_message()
    {
        $this->admin->update([
            'last_read' => now()->addMinutes(10),
        ]);

        $this->assertInstanceOf(Message::class, $this->admin->getLastSeenMessage());
        $this->assertSame($this->message->id, $this->admin->getLastSeenMessage()->id);
    }

    /** @test */
    public function participant_scope_valid_providers_ignores_company()
    {
        Messenger::setMessengerProviders(['user' => $this->getBaseProvidersConfig()['user']]);

        $this->assertSame(2, Participant::validProviders()->count());
    }
}
