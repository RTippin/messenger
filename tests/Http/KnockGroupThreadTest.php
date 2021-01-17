<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Broadcasting\KnockBroadcast;
use RTippin\Messenger\Events\KnockEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class KnockGroupThreadTest extends FeatureTestCase
{
    private Thread $group;

    protected function setUp(): void
    {
        parent::setUp();

        $this->group = $this->createGroupThread(
            $this->userTippin(),
            $this->userDoe(),
            $this->companyDevelopers()
        );
    }

    /** @test */
    public function admin_can_knock_at_thread()
    {
        $this->expectsEvents([
            KnockBroadcast::class,
            KnockEvent::class,
        ]);

        $this->actingAs($this->userTippin());

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();

        $this->assertTrue(Cache::has('knock.knock.'.$this->group->id));
    }

    /** @test */
    public function non_admin_with_permission_can_knock_at_thread()
    {
        $developers = $this->companyDevelopers();

        $this->group->participants()
            ->where('owner_id', '=', $developers->getKey())
            ->where('owner_type', '=', get_class($developers))
            ->first()
            ->update([
                'send_knocks' => true,
            ]);

        $this->expectsEvents([
            KnockBroadcast::class,
            KnockEvent::class,
        ]);

        $this->actingAs($developers);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();

        $this->assertTrue(Cache::has('knock.knock.'.$this->group->id));
    }

    /** @test */
    public function admin_forbidden_to_knock_at_thread_when_timeout_exist()
    {
        $tippin = $this->userTippin();

        Cache::put('knock.knock.'.$this->group->id, true);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_forbidden_to_knock_at_thread_when_disabled_from_settings()
    {
        $tippin = $this->userTippin();

        $this->group->update([
            'knocks' => false,
        ]);

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_forbidden_to_knock_at_thread_when_disabled_from_config()
    {
        Messenger::setKnockKnock(false);

        $tippin = $this->userTippin();

        $this->actingAs($tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_admin_without_permission_forbidden_to_knock_at_thread()
    {
        $doe = $this->userDoe();

        $this->actingAs($doe);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_participant_forbidden_to_knock_at_thread()
    {
        $this->actingAs($this->createJaneSmith());

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }
}
