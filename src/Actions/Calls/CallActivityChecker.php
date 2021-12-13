<?php

namespace RTippin\Messenger\Actions\Calls;

use Illuminate\Database\Eloquent\Collection;
use RTippin\Messenger\Actions\BaseMessengerAction;
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
     * CallActivityChecker constructor.
     *
     * @param  EndCall  $endCall
     * @param  LeaveCall  $leaveCall
     */
    public function __construct(EndCall $endCall, LeaveCall $leaveCall)
    {
        $this->endCall = $endCall;
        $this->leaveCall = $leaveCall;
    }

    /**
     * Loop through the collection of active calls given and
     * end empty calls or remove participants who are not in
     * cache and have not officially left the call.
     *
     * @param  Collection  $calls
     * @return $this
     *
     * @throws Throwable
     */
    public function execute(Collection $calls): self
    {
        $calls->each(fn (Call $call) => $this->performActivityChecks($call));

        return $this;
    }

    /**
     * @param  Call  $call
     *
     * @throws Throwable
     */
    private function performActivityChecks(Call $call): void
    {
        if (! $this->endIfEmpty($call)) {
            $this->removeInactiveParticipants($call);
        }
    }

    /**
     * @param  Call  $call
     * @return bool
     *
     * @throws Throwable
     */
    private function endIfEmpty(Call $call): bool
    {
        if (! $call->participants()->inCall()->count()) {
            $this->endCall->execute($call);

            return true;
        }

        return false;
    }

    /**
     * @param  Call  $call
     * @return void
     */
    private function removeInactiveParticipants(Call $call): void
    {
        $call->participants()
            ->inCall()
            ->each(fn (CallParticipant $participant) => $this->removeIfNotInCache($call, $participant));
    }

    /**
     * @param  Call  $call
     * @param  CallParticipant  $participant
     * @return void
     */
    private function removeIfNotInCache(Call $call, CallParticipant $participant): void
    {
        if (! $participant->isParticipantInCallCache()) {
            $this->leaveCall->execute($call, $participant);
        }
    }
}
