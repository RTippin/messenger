<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Actions\Calls\CallHeartbeat;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallHeartbeatTest extends FeatureTestCase
{
    /** @test */
    public function it_stores_call_participant_key_in_cache()
    {
        $tippin = $this->userTippin();
        $group = $this->createGroupThread($tippin);
        $call = $this->createCall($group, $tippin);
        $participant = $call->participants()->first();
        Messenger::setProvider($tippin);

        app(CallHeartbeat::class)->execute($call);

        $this->assertTrue(Cache::has("call:{$call->id}:{$participant->id}"));
    }
}
