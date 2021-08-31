<?php

namespace RTippin\Messenger\Actions\Calls;

use RTippin\Messenger\Models\Call;

class CallHeartbeat extends CallParticipantAction
{
    /**
     * Keeps the call participant in cache to show we are still in the call.
     *
     * @param Call $call
     * @return $this
     */
    public function execute(Call $call): self
    {
        $this->setCall($call)
            ->setParticipantInCallCache(
                $this->getCall()->currentCallParticipant()
            );

        return $this;
    }
}
