<?php

namespace RTippin\Messenger\Actions\Calls;

use RTippin\Messenger\Models\Call;

class CallHeartbeat extends CallParticipantAction
{
    /**
     * Keeps the call participant in cache to show we are still in the call.
     *
     * @param mixed ...$parameters
     * @var Call[0]
     * @return $this
     */
    public function execute(...$parameters): self
    {
        $this->setCall($parameters[0])
            ->setParticipantInCallCache(
                $this->getCall()->currentCallParticipant()
            );

        return $this;
    }
}
