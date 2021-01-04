<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class ParticipantsTest extends FeatureTestCase
{
    private Thread $private;

    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $this->group = $this->createGroupThread(
            $tippin,
            $doe,
            $this->companyDevelopers()
        );

        $this->private = $this->createPrivateThread(
            $tippin,
            $doe
        );
    }

    /** @test */
    public function non_participant_forbidden_to_view_participants()
    {
        $this->actingAs($this->createJaneSmith());

        $this->getJson(route('api.messenger.threads.participants.index', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_can_view_group_participants()
    {
        $this->actingAs($this->userDoe());

        $this->getJson(route('api.messenger.threads.participants.index', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function user_can_view_private_participants()
    {
        $this->actingAs($this->userDoe());

        $this->getJson(route('api.messenger.threads.participants.index', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');
    }
}
