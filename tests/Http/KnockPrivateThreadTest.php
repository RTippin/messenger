<?php

namespace RTippin\Messenger\Tests\Http;

use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Broadcasting\KnockBroadcast;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Events\KnockEvent;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class KnockPrivateThreadTest extends FeatureTestCase
{
    private Thread $private;
    private MessengerProvider $tippin;
    private MessengerProvider $doe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tippin = $this->userTippin();
        $this->doe = $this->userDoe();
        $this->private = $this->createPrivateThread($this->tippin, $this->doe);
    }

    /** @test */
    public function user_can_knock_at_thread()
    {
        $this->actingAs($this->tippin);

        $this->expectsEvents([
            KnockBroadcast::class,
            KnockEvent::class,
        ]);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $this->private->id,
        ]))
            ->assertSuccessful();
    }

    /** @test */
    public function user_forbidden_to_knock_at_thread_when_timeout_exist()
    {
        Cache::put('knock.knock.'.$this->private->id.'.'.$this->tippin->getKey(), true);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_forbidden_to_knock_at_thread_when_disabled_from_config()
    {
        Messenger::setKnockKnock(false);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_forbidden_to_knock_at_thread_when_thread_locked()
    {
        $this->doe->delete();
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function user_forbidden_to_knock_at_thread_when_awaiting_approval()
    {
        $this->private->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first()
            ->update([
                'pending' => true,
            ]);
        $this->actingAs($this->tippin);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function recipient_forbidden_to_knock_at_thread_when_awaiting_approval()
    {
        $this->private->participants()
            ->where('owner_id', '=', $this->doe->getKey())
            ->where('owner_type', '=', get_class($this->doe))
            ->first()
            ->update([
                'pending' => true,
            ]);
        $this->actingAs($this->doe);

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }

    /** @test */
    public function non_participant_forbidden_to_knock_at_thread()
    {
        $this->actingAs($this->companyDevelopers());

        $this->postJson(route('api.messenger.threads.knock', [
            'thread' => $this->private->id,
        ]))
            ->assertForbidden();
    }
}
