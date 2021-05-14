<?php

namespace RTippin\Messenger\Tests\Actions;

use RTippin\Messenger\Actions\Threads\StoreParticipant;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Support\Definitions;
use RTippin\Messenger\Tests\FeatureTestCase;

class StoreParticipantTest extends FeatureTestCase
{
    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->createGroupThread($this->tippin);
    }

    /** @test */
    public function it_stores_participant_without_supplied_attributes()
    {
        app(StoreParticipant::class)->execute(
            $this->group,
            $this->doe
        );

        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->doe->getKey(),
            'owner_type' => $this->doe->getMorphClass(),
            'thread_id' => $this->group->id,
            'admin' => false,
            'pending' => false,
        ]);
    }

    /** @test */
    public function it_stores_participant_with_admin_attributes()
    {
        app(StoreParticipant::class)->execute(
            $this->group,
            $this->doe,
            Definitions::DefaultAdminParticipant
        );

        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->doe->getKey(),
            'owner_type' => $this->doe->getMorphClass(),
            'thread_id' => $this->group->id,
            'admin' => true,
            'pending' => false,
        ]);
    }

    /** @test */
    public function it_stores_participant_with_custom_attributes()
    {
        app(StoreParticipant::class)->execute(
            $this->group,
            $this->doe,
            [
                'pending' => true,
            ]
        );

        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->doe->getKey(),
            'owner_type' => $this->doe->getMorphClass(),
            'thread_id' => $this->group->id,
            'admin' => false,
            'pending' => true,
        ]);
    }

    /** @test */
    public function it_stores_participant_if_check_restore_false()
    {
        app(StoreParticipant::class)->execute(
            $this->group,
            $this->doe,
            [],
            false
        );

        $this->assertDatabaseHas('participants', [
            'owner_id' => $this->doe->getKey(),
            'owner_type' => $this->doe->getMorphClass(),
            'thread_id' => $this->group->id,
            'admin' => false,
            'pending' => false,
        ]);
    }

    /** @test */
    public function it_restores_participant_if_check_restore_true()
    {
        $participant = Participant::factory()->for($this->group)->owner($this->doe)->create(['deleted_at' => now()]);

        app(StoreParticipant::class)->execute(
            $this->group,
            $this->doe,
            [],
            true
        );

        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'deleted_at' => null,
        ]);
    }
}
