<?php

namespace RTippin\Messenger\Tests\Actions;

use Illuminate\Support\Facades\Cache;
use RTippin\Messenger\Actions\Calls\CallHeartbeat;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Tests\FeatureTestCase;

class CallHeartbeatTest extends FeatureTestCase
{
    private Call $call;

    private CallParticipant $participant;

    protected function setUp(): void
    {
        parent::setUp();

        $tippin = $this->userTippin();

        $group = $this->createGroupThread($tippin);

        $this->call = $this->createCall($group, $tippin);

        $this->participant = $this->call->participants()->first();

        Messenger::setProvider($tippin);
    }

    /** @test */
    public function call_heartbeat_sets_participant_in_cache()
    {
        app(CallHeartbeat::class)->execute($this->call);

        $this->assertTrue(Cache::has("call:{$this->call->id}:{$this->participant->id}"));
    }
}
