<?php

namespace RTippin\Messenger\Tests\Http;

use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class StartCallTest extends FeatureTestCase
{
    private Thread $private;

    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $doe = $this->userDoe();

        $this->private = $this->createPrivateThread(
            $tippin,
            $doe
        );

        $this->group = $this->createGroupThread(
            $tippin,
            $doe,
            $this->companyDevelopers()
        );
    }

    /** @test */
    public function non_participant_forbidden_to_start_call_in_private()
    {
        $this->actingAs($this->companyDevelopers());

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_participant_forbidden_to_start_call_in_group()
    {
        $this->actingAs($this->companyLaravel());

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }
}
