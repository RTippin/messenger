<?php

namespace RTippin\Messenger\Actions\Calls;

use Illuminate\Contracts\Cache\Repository;
use RTippin\Messenger\Models\Call;

class CallHeartbeat extends CallParticipantAction
{
    /**
     * CallHeartbeat constructor.
     *
     * @param Repository $cacheDriver
     */
    public function __construct(Repository $cacheDriver)
    {
        parent::__construct($cacheDriver);
    }

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
                $this->getCall()
                    ->currentCallParticipant()
            );

        return $this;
    }
}
