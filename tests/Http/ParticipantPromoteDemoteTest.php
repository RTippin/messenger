<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class ParticipantPromoteDemoteTest extends FeatureTestCase
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
    public function user_forbidden_to_promote_admin_role_in_private_thread()
    {
        $doe = $this->userDoe();

        $tippin = $this->userTippin();

        $participant = $this->private->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first();

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.participants.promote', [
            'thread' => $this->private->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_forbidden_to_demote_admin_role_in_private_thread()
    {
        $doe = $this->userDoe();

        $tippin = $this->userTippin();

        $participant = $this->private->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first();

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.participants.demote', [
            'thread' => $this->private->id,
            'participant' => $participant->id,
        ]))
            ->assertForbidden();
    }
}
