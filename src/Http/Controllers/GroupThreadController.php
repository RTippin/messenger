<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use RTippin\Messenger\Actions\Threads\DestroyGroupAvatar;
use RTippin\Messenger\Actions\Threads\LeaveThread;
use RTippin\Messenger\Actions\Threads\StoreGroupAvatar;
use RTippin\Messenger\Actions\Threads\StoreGroupThread;
use RTippin\Messenger\Actions\Threads\UpdateGroupSettings;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Http\Collections\GroupThreadCollection;
use RTippin\Messenger\Http\Request\GroupAvatarRequest;
use RTippin\Messenger\Http\Request\GroupThreadRequest;
use RTippin\Messenger\Http\Request\ThreadSettingsRequest;
use RTippin\Messenger\Http\Resources\ThreadResource;
use RTippin\Messenger\Http\Resources\ThreadSettingsResource;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Repositories\GroupThreadRepository;
use Throwable;

class GroupThreadController
{
    use AuthorizesRequests;

    /**
     * Display a listing of the most recent group threads.
     *
     * @param  GroupThreadRepository  $repository
     * @return GroupThreadCollection
     *
     * @throws AuthorizationException
     */
    public function index(GroupThreadRepository $repository): GroupThreadCollection
    {
        $this->authorize('viewAny', Thread::class);

        return new GroupThreadCollection(
            $repository->getProviderGroupThreadsIndex()
        );
    }

    /**
     * Display group threads history pagination.
     *
     * @param  GroupThreadRepository  $repository
     * @param  Thread  $group
     * @return GroupThreadCollection
     *
     * @throws AuthorizationException
     */
    public function paginate(GroupThreadRepository $repository, Thread $group): GroupThreadCollection
    {
        $this->authorize('groupMethod', $group);

        return new GroupThreadCollection(
            $repository->getProviderGroupThreadsPage($group),
            true,
            $group->id
        );
    }

    /**
     * Display group thread settings.
     *
     * @param  Thread  $thread
     * @return ThreadSettingsResource
     *
     * @throws AuthorizationException
     */
    public function settings(Thread $thread): ThreadSettingsResource
    {
        $this->authorize('settings', $thread);

        return new ThreadSettingsResource($thread);
    }

    /**
     * Update the group settings.
     *
     * @param  ThreadSettingsRequest  $request
     * @param  UpdateGroupSettings  $updateGroupSettings
     * @param  Thread  $thread
     * @return ThreadSettingsResource
     *
     * @throws AuthorizationException
     */
    public function updateSettings(ThreadSettingsRequest $request,
                                   UpdateGroupSettings $updateGroupSettings,
                                   Thread $thread): ThreadSettingsResource
    {
        $this->authorize('settings', $thread);

        return $updateGroupSettings->execute(
            $thread,
            $request->validated()
        )->getJsonResource();
    }

    /**
     * Store the group avatar.
     *
     * @param  GroupAvatarRequest  $request
     * @param  StoreGroupAvatar  $storeGroupAvatar
     * @param  Thread  $thread
     * @return ThreadSettingsResource
     *
     * @throws FeatureDisabledException|AuthorizationException
     */
    public function storeAvatar(GroupAvatarRequest $request,
                                StoreGroupAvatar $storeGroupAvatar,
                                Thread $thread): ThreadSettingsResource
    {
        $this->authorize('settings', $thread);

        return $storeGroupAvatar->execute(
            $thread,
            $request->validated()['image']
        )->getJsonResource();
    }

    /**
     * Remove the group avatar.
     *
     * @param  DestroyGroupAvatar  $destroyGroupAvatar
     * @param  Thread  $thread
     * @return ThreadSettingsResource
     *
     * @throws AuthorizationException|FeatureDisabledException
     */
    public function destroyAvatar(DestroyGroupAvatar $destroyGroupAvatar, Thread $thread): ThreadSettingsResource
    {
        $this->authorize('settings', $thread);

        return $destroyGroupAvatar->execute($thread)->getJsonResource();
    }

    /**
     * Store a new group thread.
     *
     * @param  GroupThreadRequest  $request
     * @param  StoreGroupThread  $storeGroupThread
     * @return ThreadResource
     *
     * @throws Throwable
     */
    public function store(GroupThreadRequest $request, StoreGroupThread $storeGroupThread): ThreadResource
    {
        $this->authorize('create', Thread::class);

        return $storeGroupThread->execute(
            $request->validated()
        )->getJsonResource();
    }

    /**
     * Leave a group thread.
     *
     * @param  LeaveThread  $leaveThread
     * @param  Thread  $thread
     * @return JsonResponse
     *
     * @throws AuthorizationException|Throwable
     */
    public function leave(LeaveThread $leaveThread, Thread $thread): JsonResponse
    {
        $this->authorize('leave', $thread);

        return $leaveThread->execute($thread)->getMessageResponse();
    }
}
