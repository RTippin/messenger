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

    /** @test */
    public function participant_without_permission_forbidden_to_start_call()
    {
        $this->actingAs($this->userDoe());

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_start_call_on_pending_private()
    {
        $doe = $this->userDoe();

        $this->private->participants()
            ->where('owner_id', '=', $doe->getKey())
            ->where('owner_type', '=', get_class($doe))
            ->first()
            ->update([
                'pending' => true,
            ]);

        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function forbidden_to_start_call_when_active_call_exist()
    {
        $tippin = $this->userTippin();

        $this->createCall($this->private, $tippin);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.calls.store', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

//    /** @test */
//    public function forbidden_to_start_call_when_creating_call_timeout_exist()
//    {
//
//        $this->actingAs($this->userTippin());
//
//        $test = $this->postJson(route('api.messenger.threads.calls.store', [
//            'thread' => $this->private->id,
//        ]))
//            ->assertForbidden();
//
//        dump($test->getContent());
//    }
}
