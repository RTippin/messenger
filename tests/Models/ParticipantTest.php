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
    /** @test */
    public function it_exists()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();
        Participant::factory()->for($thread)->owner($this->doe)->create();

        $this->assertDatabaseCount('participants', 2);
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
        ]);
        $this->assertInstanceOf(Participant::class, $participant);
        $this->assertSame(1, Participant::admins()->count());
        $this->assertSame(2, Participant::notMuted()->count());
        $this->assertSame(2, Participant::notPending()->count());
        $this->assertSame(2, Participant::validProviders()->count());
    }

    /** @test */
    public function it_cast_attributes()
    {
        Participant::factory()->for(
            Thread::factory()->group()->create()
        )->owner($this->tippin)->admin()->read()->trashed()->create();
        $participant = Participant::withTrashed()->first();

        $this->assertInstanceOf(Carbon::class, $participant->created_at);
        $this->assertInstanceOf(Carbon::class, $participant->updated_at);
        $this->assertInstanceOf(Carbon::class, $participant->deleted_at);
        $this->assertInstanceOf(Carbon::class, $participant->last_read);
        $this->assertTrue($participant->admin);
        $this->assertTrue($participant->send_messages);
        $this->assertTrue($participant->send_knocks);
        $this->assertTrue($participant->start_calls);
        $this->assertTrue($participant->add_participants);
        $this->assertTrue($participant->manage_invites);
        $this->assertTrue($participant->manage_bots);
        $this->assertFalse($participant->muted);
        $this->assertFalse($participant->pending);
    }

    /** @test */
    public function it_has_relations()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->admin()->create();

        $this->assertSame($this->tippin->getKey(), $participant->owner->getKey());
        $this->assertSame($thread->id, $participant->thread->id);
        $this->assertInstanceOf(Thread::class, $participant->thread);
        $this->assertInstanceOf(MessengerProvider::class, $participant->owner);
        $this->assertInstanceOf(Collection::class, $participant->messages);
    }

    /** @test */
    public function owner_returns_ghost_if_not_found()
    {
        $participant = Participant::factory()->for(Thread::factory()->create())->create([
            'owner_id' => 404,
            'owner_type' => $this->tippin->getMorphClass(),
        ]);

        $this->assertInstanceOf(GhostUser::class, $participant->owner);
    }

    /** @test */
    public function it_has_last_seen_message()
    {
        $thread = Thread::factory()->group()->create();
        $message = Message::factory()->for($thread)->owner($this->doe)->create();
        $participant = Participant::factory()->for($thread)->owner($this->tippin)->read()->create();

        $this->assertInstanceOf(Message::class, $participant->getLastSeenMessage());
        $this->assertSame($message->id, $participant->getLastSeenMessage()->id);
    }

    /** @test */
    public function valid_providers_scope_ignores_company()
    {
        Messenger::setMessengerProviders(['user' => $this->getBaseProvidersConfig()['user']]);
        $thread = Thread::factory()->group()->create();
        Participant::factory()->for($thread)->owner($this->tippin)->create();
        Participant::factory()->for($thread)->owner($this->developers)->create();

        $this->assertSame(2, Participant::count());
        $this->assertSame(1, Participant::validProviders()->count());
    }
}
