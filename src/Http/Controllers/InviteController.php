<?php

namespace RTippin\Messenger\Http\Controllers;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Actions\Invites\ArchiveInvite;
use RTippin\Messenger\Actions\Invites\StoreInvite;
use RTippin\Messenger\Http\Collections\InviteCollection;
use RTippin\Messenger\Http\Request\InviteRequest;
use RTippin\Messenger\Http\Resources\InviteResource;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Thread;

class InviteController
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     *
     * @param Thread $thread
     * @return InviteCollection
     * @throws AuthorizationException
     */
    public function index(Thread $thread): InviteCollection
    {
        $this->authorize('viewAny', [
            Invite::class,
            $thread,
        ]);

        return new InviteCollection(
            $thread->invites()
                ->valid()
                ->latest()
                ->with('owner')
                ->get(),
            $thread
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param InviteRequest $request
     * @param StoreInvite $storeInvite
     * @param Thread $thread
     * @return InviteResource
     * @throws AuthorizationException
     */
    public function store(InviteRequest $request,
                          StoreInvite $storeInvite,
                          Thread $thread): InviteResource
    {
        $this->authorize('create', [
            Invite::class,
            $thread,
        ]);

        return $storeInvite->execute(
            $thread,
            $request->validated()
        )->getJsonResource();
    }

    /**
     * Show the invite without owner. This is for public consumption.
     *
     * @param Invite $invite
     * @return InviteResource
     */
    public function show(Invite $invite): InviteResource
    {
        return new InviteResource($invite, true);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param ArchiveInvite $archiveInvite
     * @param Thread $thread
     * @param Invite $invite
     * @return JsonResponse
     * @throws AuthorizationException|Exception
     */
    public function destroy(ArchiveInvite $archiveInvite,
                            Thread $thread,
                            Invite $invite): JsonResponse
    {
        $this->authorize('delete', [
            Invite::class,
            $thread,
        ]);

        return $archiveInvite->execute($invite)
            ->getMessageResponse();
    }
}
