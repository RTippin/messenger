<?php

namespace RTippin\Messenger\Http\Controllers;

use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Actions\Invites\ArchiveInvite;
use RTippin\Messenger\Actions\Invites\StoreInvite;
use RTippin\Messenger\Http\Collections\InviteCollection;
use RTippin\Messenger\Http\Request\InviteRequest;
use RTippin\Messenger\Http\Resources\InviteResource;
use RTippin\Messenger\Models\Invite;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\ImageRenderService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InviteController
{
    use AuthorizesRequests;

    /**
     * Display all invites for the group thread.
     *
     * @param  Thread  $thread
     * @return InviteCollection
     *
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
     * Store a new group invite.
     *
     * @param  InviteRequest  $request
     * @param  StoreInvite  $storeInvite
     * @param  Thread  $thread
     * @return InviteResource
     *
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
     * @param  Invite  $invite
     * @return InviteResource
     */
    public function show(Invite $invite): InviteResource
    {
        return new InviteResource($invite, true);
    }

    /**
     * Render invites group avatar.
     *
     * @param  ImageRenderService  $service
     * @param  Invite  $invite
     * @param  string  $size
     * @param  string  $image
     * @return StreamedResponse|BinaryFileResponse
     *
     * @throws AuthorizationException
     * @throws FileNotFoundException
     */
    public function renderAvatar(ImageRenderService $service,
                                 Invite $invite,
                                 string $size,
                                 string $image)
    {
        if (! $invite->isValid()) {
            throw new AuthorizationException('Not authorized to view invite avatar.');
        }

        return $service->renderGroupAvatar($invite->thread, $size, $image);
    }

    /**
     * Remove the group invite.
     *
     * @param  ArchiveInvite  $archiveInvite
     * @param  Thread  $thread
     * @param  Invite  $invite
     * @return JsonResponse
     *
     * @throws AuthorizationException|Exception
     */
    public function destroy(ArchiveInvite $archiveInvite,
                            Thread $thread,
                            Invite $invite): JsonResponse
    {
        $this->authorize('delete', [
            $invite,
            $thread,
        ]);

        return $archiveInvite->execute($invite)->getMessageResponse();
    }
}
