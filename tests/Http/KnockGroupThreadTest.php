<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Broadcasting\KnockBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\KnockEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class KnockGroupThreadTest extends FeatureTestCase
{
    private Thread $group;
    private MessengerProvider $tippin;
    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->doe = $this->userDoe();
        $this->group = $this->createGroupThread($this->tippin, $this->doe);
    }

    /** @test */
    public function admin_can_knock_at_thread()
    {
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            KnockBroadcast::class,
            KnockEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function non_admin_with_permission_can_knock_at_thread()
    {
        $this->group->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first()
            ->update([
                'send_knocks' => true,
            ]);
        $this->actingAs($this->doe);

        $this->expectsEvents([
            KnockBroadcast::class,
            KnockEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $this->group->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function admin_forbidden_to_knock_at_thread_when_timeout_exist()
    {
        Cache::put('knock.knock.'.$this->group->id, true);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_forbidden_to_knock_at_thread_when_disabled_from_settings()
    {
        $this->group->update([
            'knocks' => false,
        ]);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_forbidden_to_knock_at_thread_when_thread_locked()
    {
        $this->group->update([
            'lockout' => true,
        ]);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function admin_forbidden_to_knock_at_thread_when_disabled_from_config()
    {
        Messenger::setKnockKnock(false);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $this->group->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_admin_without_permission_forbidden_to_knock_at_thread()
    {
        $this->actingAs($this->doe);

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
