<?php

namespace RTippin\Messenger\Http\Controllers;

use RTippin\Messenger\Actions\Messenger\DestroyMessengerAvatar;
use RTippin\Messenger\Actions\Messenger\StoreMessengerAvatar;
use RTippin\Messenger\Actions\Messenger\UpdateMessengerSettings;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
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
    public function index(): array
    {
        return $this->messenger->getConfig();
    }

    /**
     * @return MessengerResource
     */
    public function settings(): MessengerResource
    {
        return new MessengerResource(
            $this->messenger->getProviderMessenger()
        );
    }

    /**
     * @param ThreadRepository $threadRepository
     * @return ActiveCallCollection
     */
    public function activeCalls(ThreadRepository $threadRepository): ActiveCallCollection
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
                                   UpdateMessengerSettings $updateMessengerSettings): MessengerResource
    {
        $updateMessengerSettings->execute(
            $request->validated()
        );

        return new MessengerResource(
            $this->messenger->getProviderMessenger()
        );
    }

    /**
     * @param MessengerAvatarRequest $request
     * @param StoreMessengerAvatar $storeMessengerAvatar
     * @return MessengerResource
     * @throws FeatureDisabledException
     */
    public function updateAvatar(MessengerAvatarRequest $request,
                                 StoreMessengerAvatar $storeMessengerAvatar): MessengerResource
    {
        $storeMessengerAvatar->execute($request->validated());

        return new MessengerResource(
            $this->messenger->getProviderMessenger()
        );
    }

    /**
     * @param DestroyMessengerAvatar $destroyMessengerAvatar
     * @return MessengerResource
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
