<?php

namespace RTippin\Messenger\Tests\Actions;

use RTippin\Messenger\Actions\Threads\StoreParticipant;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreParticipantTest extends FeatureTestCase
{
    /** @test */
    public function it_stores_participant_without_supplied_attributes()
    {
        $thread = Thread::factory()->group()->create();

        app(StoreParticipant::class)->execute($thread, $this->doe);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->doe->getKey(),
            'owner_type' => $this->doe->getMorphClass(),
            'thread_id' => $thread->id,
            'admin' => false,
            'pending' => false,
        ]);
    }

    /** @test */
    public function it_stores_participant_with_admin_attributes()
    {
        $thread = Thread::factory()->group()->create();

        app(StoreParticipant::class)->execute($thread, $this->doe, Participant::AdminPermissions);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->doe->getKey(),
            'owner_type' => $this->doe->getMorphClass(),
            'thread_id' => $thread->id,
            'admin' => true,
            'pending' => false,
        ]);
    }

    /** @test */
    public function it_stores_participant_with_custom_attributes()
    {
        $thread = Thread::factory()->group()->create();

        app(StoreParticipant::class)->execute($thread, $this->doe, ['pending' => true]);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->doe->getKey(),
            'owner_type' => $this->doe->getMorphClass(),
            'thread_id' => $thread->id,
            'admin' => false,
            'pending' => true,
        ]);
    }

    /** @test */
    public function it_stores_participant_if_check_restore_false()
    {
        $thread = Thread::factory()->group()->create();

        app(StoreParticipant::class)->execute($thread, $this->doe, [], false);

        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->doe->getKey(),
            'owner_type' => $this->doe->getMorphClass(),
            'thread_id' => $thread->id,
            'admin' => false,
            'pending' => false,
        ]);
    }

    /** @test */
    public function it_restores_participant_if_check_restore_true()
    {
        $thread = Thread::factory()->group()->create();
        $participant = Participant::factory()->for($thread)->owner($this->doe)->trashed()->create();

        app(StoreParticipant::class)->execute($thread, $this->doe, [], true);

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'deleted_at' => null,
        ]);
    }
}
