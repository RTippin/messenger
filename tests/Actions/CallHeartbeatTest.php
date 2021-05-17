<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Actions\Calls\CallHeartbeat;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallHeartbeatTest extends FeatureTestCase
{
    /** @test */
    public function it_stores_call_participant_key_in_cache()
    {
        $call = Call::factory()->for(Thread::factory()->create())->owner($this->tippin)->setup()->create();
        $participant = CallParticipant::factory()->for($call)->owner($this->tippin)->create();
        Messenger::setProvider($this->tippin);

        app(CallHeartbeat::class)->execute($call);

        $this->assertTrue(Cache::has("call:$call->id:$participant->id"));
    }
}
