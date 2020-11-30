<?php

namespace RTippin\Messenger\Actions\Calls;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Collection;
use Psr\SimpleCache\InvalidArgumentException;
use RTippin\Messenger\Actions\Base\BaseMessengerAction;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use Throwable;

class CallActivityChecker extends BaseMessengerAction
{
    /**
     * @var EndCall
     */
    private EndCall $endCall;

    /**
     * @var LeaveCall
     */
    private LeaveCall $leaveCall;

    /**
     * @var Repository
     */
    private Repository $cacheDriver;

    /**
     * CallActivityChecker constructor.
     *
     * @param Repository $cacheDriver
     * @param EndCall $endCall
     * @param LeaveCall $leaveCall
     */
    public function __construct(Repository $cacheDriver,
                                EndCall $endCall,
                                LeaveCall $leaveCall)
    {
        $this->endCall = $endCall;
        $this->leaveCall = $leaveCall;
        $this->cacheDriver = $cacheDriver;
    }

    /**
     * Loop through the collection of active calls we got and
     * end empty calls or remove participants who are not in
     * cache and have not officially left the call
     *
     * @param mixed ...$parameters
     * @var Collection $calls $parameters[0]
     * @return $this
     * @throws Throwable
     */
    public function execute(...$parameters): self
    {
        /** @var Collection $calls */

        $calls = $parameters[0];

        $calls->each(
            fn(Call $call) => $this->performActivityChecks($call)
        );

        return $this;
    }

    /**
     * @param Call $call
     * @throws Throwable
     */
    private function performActivityChecks(Call $call): void
    {
        if( ! $this->endIfEmpty($call))
        {
            $this->removeInactiveParticipants($call);
        }
    }

    /**
     * @param Call $call
     * @return bool
     * @throws Throwable
     */
    private function endIfEmpty(Call $call): bool
    {
        if( ! $call->participants()->inCall()->count())
        {
            $this->endCall->execute($call);

            return true;
        }

        return false;
    }

    /**
     * @param Call $call
     */
    private function removeInactiveParticipants(Call $call)
    {
        $call->participants()->inCall()->each(
            fn(CallParticipant $participant) => $this->removeIfNotInCache($call, $participant)
        );
    }

    /**
     * @param Call $call
     * @param CallParticipant $participant
     * @throws InvalidArgumentException
     */
    private function removeIfNotInCache(Call $call, CallParticipant $participant): void
    {
        if( ! $this->cacheDriver->has("call:{$call->id}:{$participant->id}"))
        {
            $this->leaveCall->execute($call, $participant);
        }
    }
}