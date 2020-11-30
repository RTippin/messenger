<?php

namespace RTippin\Messenger\Http\Controllers;

use Illuminate\Auth\Access\AuthorizationException;
use RTippin\Messenger\Actions\DestroyMessengerAvatar;
use RTippin\Messenger\Actions\StoreMessengerAvatar;
use RTippin\Messenger\Actions\UpdateMessengerSettings;
use RTippin\Messenger\Http\Collections\ActiveCallCollection;
use RTippin\Messenger\Http\Request\MessengerAvatarRequest;
use RTippin\Messenger\Http\Request\MessengerSettingsRequest;
use RTippin\Messenger\Http\Resources\MessengerResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Repositories\ThreadRepository;

class MessengerController
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * MessengerController constructor.
     *
     * @param Messenger $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * @return array
     */
    public function index()
    {
        return $this->messenger->getConfig();
    }

    /**
     * @return MessengerResource
     */
    public function settings()
    {
        return new MessengerResource(
            $this->messenger->getProvider()->messenger
        );
    }

    /**
     * @param ThreadRepository $threadRepository
     * @return ActiveCallCollection
     */
    public function activeCalls(ThreadRepository $threadRepository)
    {
        return new ActiveCallCollection(
            $threadRepository->getProviderThreadsWithActiveCalls()
        );
    }

    /**
     * @param MessengerSettingsRequest $request
     * @param UpdateMessengerSettings $updateMessengerSettings
     * @return MessengerResource
     */
    public function updateSettings(MessengerSettingsRequest $request,
                                   UpdateMessengerSettings $updateMessengerSettings)
    {
        $updateMessengerSettings->execute(
            $request->validated()
        );

        return new MessengerResource(
            $this->messenger->getProvider()->messenger
        );
    }

    /**
     * @param MessengerAvatarRequest $request
     * @param StoreMessengerAvatar $storeMessengerAvatar
     * @return MessengerResource
     * @throws AuthorizationException
     */
    public function updateAvatar(MessengerAvatarRequest $request,
                                 StoreMessengerAvatar $storeMessengerAvatar)
    {
        $this->allowedToUploadAvatar();

        $storeMessengerAvatar->execute($request->validated());

        return new MessengerResource(
            $this->messenger->getProvider()->messenger
        );
    }

    /**
     * @param DestroyMessengerAvatar $destroyMessengerAvatar
     * @return MessengerResource
     * @throws AuthorizationException
     */
    public function destroyAvatar(DestroyMessengerAvatar $destroyMessengerAvatar)
    {
        $this->allowedToRemoveAvatar();

        $destroyMessengerAvatar->execute();

        return new MessengerResource(
            $this->messenger->getProvider()->messenger
        );
    }

    /**
     * @throws AuthorizationException
     */
    private function allowedToUploadAvatar()
    {
        if( ! $this->messenger->isAvatarUploadEnabled())
        {
            throw new AuthorizationException("Avatar uploads are currently disabled.");
        }
    }

    /**
     * @throws AuthorizationException
     */
    private function allowedToRemoveAvatar()
    {
        if( ! $this->messenger->isAvatarRemovalEnabled())
        {
            throw new AuthorizationException("Avatar removal is currently disabled.");
        }
    }
}