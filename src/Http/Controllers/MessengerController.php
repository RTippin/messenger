<?php

namespace RTippin\Messenger\Http\Controllers;

use Exception;
use RTippin\Messenger\Actions\Messenger\DestroyMessengerAvatar;
use RTippin\Messenger\Actions\Messenger\StoreMessengerAvatar;
use RTippin\Messenger\Actions\Messenger\UpdateMessengerSettings;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\FileServiceException;
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
     * @param  Messenger  $messenger
     */
    public function __construct(Messenger $messenger)
    {
        $this->messenger = $messenger;
    }

    /**
     * Display the messenger system settings.
     *
     * @return array
     */
    public function index(): array
    {
        return $this->messenger->getConfig();
    }

    /**
     * Display the provider's messenger.
     *
     * @return MessengerResource
     */
    public function settings(): MessengerResource
    {
        return new MessengerResource(
            $this->messenger->getProviderMessenger()
        );
    }

    /**
     * Display a listing of the providers active calls.
     *
     * @param  ThreadRepository  $threadRepository
     * @return ActiveCallCollection
     */
    public function activeCalls(ThreadRepository $threadRepository): ActiveCallCollection
    {
        return new ActiveCallCollection(
            $threadRepository->getProviderThreadsWithActiveCalls()
        );
    }

    /**
     * Update the providers messenger settings.
     *
     * @param  MessengerSettingsRequest  $request
     * @param  UpdateMessengerSettings  $settings
     * @return MessengerResource
     */
    public function updateSettings(MessengerSettingsRequest $request, UpdateMessengerSettings $settings): MessengerResource
    {
        $settings->execute($request->validated());

        return new MessengerResource(
            $this->messenger->getProviderMessenger()
        );
    }

    /**
     * Update the providers avatar.
     *
     * @param  MessengerAvatarRequest  $request
     * @param  StoreMessengerAvatar  $storeAvatar
     * @return MessengerResource
     *
     * @throws FeatureDisabledException|FileServiceException|Exception
     */
    public function updateAvatar(MessengerAvatarRequest $request, StoreMessengerAvatar $storeAvatar): MessengerResource
    {
        $storeAvatar->execute($request->validated()['image']);

        return new MessengerResource(
            $this->messenger->getProviderMessenger()
        );
    }

    /**
     * Remove the providers avatar.
     *
     * @param  DestroyMessengerAvatar  $destroyMessengerAvatar
     * @return MessengerResource
     *
     * @throws FeatureDisabledException
     */
    public function destroyAvatar(DestroyMessengerAvatar $destroyMessengerAvatar): MessengerResource
    {
        $destroyMessengerAvatar->execute();

        return new MessengerResource(
            $this->messenger->getProviderMessenger()
        );
    }
}
