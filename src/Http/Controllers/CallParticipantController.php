<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Actions\Calls\KickCallParticipant;
use RTippin\Messenger\Http\Collections\CallParticipantCollection;
use RTippin\Messenger\Http\Request\KickCallParticipantRequest;
use RTippin\Messenger\Http\Resources\CallParticipantResource;
use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\CallParticipant;
use RTippin\Messenger\Models\Thread;

class CallParticipantController
{
    use AuthorizesRequests;

    /**
     * Display a listing of the calls participants.
     *
     * @param  Thread  $thread
     * @param  Call  $call
     * @return CallParticipantCollection
     *
     * @throws AuthorizationException
     */
    public function index(Thread $thread, Call $call): CallParticipantCollection
    {
        $this->authorize('viewAny', [
            CallParticipant::class,
            $thread,
            $call,
        ]);

        return new CallParticipantCollection(
            $call->participants->load('owner')
        );
    }

    /**
     * Display the call  participant.
     *
     * @param  Thread  $thread
     * @param  Call  $call
     * @param  CallParticipant  $participant
     * @return CallParticipantResource
     *
     * @throws AuthorizationException
     */
    public function show(Thread $thread,
                         Call $call,
                         CallParticipant $participant): CallParticipantResource
    {
        $this->authorize('view', [
            $participant,
            $thread,
            $call,
        ]);

        return new CallParticipantResource(
            $participant->load('owner')
        );
    }

    /**
     * Kick or un-kick call participant.
     *
     * @param  KickCallParticipantRequest  $request
     * @param  KickCallParticipant  $kickCallParticipant
     * @param  Thread  $thread
     * @param  Call  $call
     * @param  CallParticipant  $participant
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function update(KickCallParticipantRequest $request,
                           KickCallParticipant $kickCallParticipant,
                           Thread $thread,
                           Call $call,
                           CallParticipant $participant): JsonResponse
    {
        $this->authorize('update', [
            $participant,
            $thread,
            $call,
        ]);

        return $kickCallParticipant->execute(
            $call,
            $participant,
            $request->validated()['kicked']
        )->getMessageResponse();
    }
}
