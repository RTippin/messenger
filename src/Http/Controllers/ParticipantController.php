<?php

namespace RTippin\Messenger\Http\Controllers;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use RTippin\Messenger\Actions\Threads\RemoveParticipant;
use RTippin\Messenger\Actions\Threads\StoreManyParticipants;
use RTippin\Messenger\Actions\Threads\UpdateParticipantPermissions;
use RTippin\Messenger\Http\Collections\ParticipantCollection;
use RTippin\Messenger\Http\Request\AddParticipantsRequest;
use RTippin\Messenger\Http\Request\ParticipantPermissionsRequest;
use RTippin\Messenger\Http\Resources\ParticipantResource;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\ParticipantRepository;
use Throwable;

class ParticipantController
{
    use AuthorizesRequests;

    /**
     * Display a listing of the participants, oldest first.
     *
     * @param ParticipantRepository $repository
     * @param Thread $thread
     * @return ParticipantCollection
     * @throws AuthorizationException
     */
    public function index(ParticipantRepository $repository, Thread $thread)
    {
        $this->authorize('viewAny', [
            Participant::class,
            $thread
        ]);

        return new ParticipantCollection(
            $repository->getThreadParticipantsIndex($thread),
            $thread
        );
    }

    /**
     * Display participant history pagination
     *
     * @param ParticipantRepository $repository
     * @param Thread $thread
     * @param Participant $participant
     * @return ParticipantCollection
     * @throws AuthorizationException
     */
    public function paginate(ParticipantRepository $repository,
                                        Thread $thread,
                                        Participant $participant)
    {
        $this->authorize('viewAny', [
            Participant::class,
            $thread
        ]);

        return new ParticipantCollection(
            $repository->getThreadParticipantsPage($thread, $participant),
            $thread,
            true,
            $participant->id
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param AddParticipantsRequest $request
     * @param StoreManyParticipants $storeManyParticipants
     * @param Thread $thread
     * @return JsonResponse|JsonResource|ResourceCollection
     * @throws AuthorizationException|Throwable
     */
    public function store(AddParticipantsRequest $request,
                          StoreManyParticipants $storeManyParticipants,
                          Thread $thread)
    {
        $this->authorize('create', [
            Participant::class,
            $thread
        ]);

        return $storeManyParticipants->execute(
            $thread,
            $request->validated()['providers']
        )->getData();
    }

    /**
     * Display the specified resource.
     *
     * @param Thread $thread
     * @param Participant $participant
     * @return ParticipantResource
     * @throws AuthorizationException
     */
    public function show(Thread $thread, Participant $participant)
    {
        $this->authorize('view', [
            Participant::class,
            $thread
        ]);

        return new ParticipantResource(
            $participant->load('owner'),
            $thread
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ParticipantPermissionsRequest $request
     * @param UpdateParticipantPermissions $permissions
     * @param Thread $thread
     * @param Participant $participant
     * @return ParticipantResource
     * @throws AuthorizationException
     */
    public function update(ParticipantPermissionsRequest $request,
                           UpdateParticipantPermissions $permissions,
                           Thread $thread,
                           Participant $participant)
    {
        $this->authorize('update', [
            $participant,
            $thread
        ]);

        return $permissions->execute(
            $thread,
            $participant,
            $request->validated()
        )->getJsonResource();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param RemoveParticipant $removeParticipant
     * @param Thread $thread
     * @param Participant $participant
     * @return JsonResponse|mixed|null
     * @throws AuthorizationException|Exception
     */
    public function destroy(RemoveParticipant $removeParticipant,
                            Thread $thread,
                            Participant $participant)
    {
        $this->authorize('delete', [
            $participant,
            $thread
        ]);

        return $removeParticipant->execute(
            $thread,
            $participant
        )->getMessageResponse();
    }
}