<?php

namespace RTippin\Messenger\Actions\Threads;

use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\ThreadAvatarBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Events\ThreadAvatarEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Exceptions\FileServiceException;
use RTippin\Messenger\Http\Request\GroupAvatarRequest;
use RTippin\Messenger\Http\Resources\Broadcast\ThreadSettingsBroadcastResource;
use RTippin\Messenger\Http\Resources\ThreadSettingsResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\FileService;
use RTippin\Messenger\Support\Definitions;
use Throwable;

class UpdateGroupAvatar extends BaseMessengerAction
{
    /**
     * @var Messenger
     */
    private Messenger $messenger;

    /**
     * @var BroadcastDriver
     */
    private BroadcastDriver $broadcaster;

    /**
     * @var Dispatcher
     */
    private Dispatcher $dispatcher;

    /**
     * @var FileService
     */
    private FileService $fileService;

    /**
     * @var string|null
     */
    private ?string $originalAvatar;

    /**
     * @var bool
     */
    private bool $usingDefault = false;

    /**
     * @var string
     */
    private string $theDefaultImage;

    /**
     * UpdateGroupAvatar constructor.
     *
     * @param Messenger $messenger
     * @param BroadcastDriver $broadcaster
     * @param Dispatcher $dispatcher
     * @param FileService $fileService
     */
    public function __construct(Messenger $messenger,
                                BroadcastDriver $broadcaster,
                                Dispatcher $dispatcher,
                                FileService $fileService)
    {
        $this->broadcaster = $broadcaster;
        $this->dispatcher = $dispatcher;
        $this->messenger = $messenger;
        $this->fileService = $fileService;
    }

    /**
     * Check if the avatar is changing, then whether we are
     * picking a default or uploading a new avatar!
     *
     * @param mixed ...$parameters
     * @return $this
     * @throws FeatureDisabledException|FileServiceException|Exception
     * @var Thread[0]
     * @var GroupAvatarRequest[1]
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0])
            ->setOriginalAvatar()
            ->determineIfDefault($parameters[1]);

        if ($this->avatarChanged()) {
            $this->handleUpdatingAvatar($parameters[1]);

            $this->fireBroadcast()->fireEvents();
        }

        $this->generateResource();

        return $this;
    }

    /**
     * @return $this
     */
    private function setOriginalAvatar(): self
    {
        $this->originalAvatar = $this->getThread()->image;

        return $this;
    }

    /**
     * @param array $params
     */
    private function determineIfDefault(array $params): void
    {
        if (array_key_exists('default', $params)) {
            $this->usingDefault = true;
            $this->theDefaultImage = $params['default'];
        }
    }

    /**
     * @return bool
     */
    private function avatarChanged(): bool
    {
        return ! $this->usingDefault || $this->getThread()->image !== $this->theDefaultImage;
    }

    /**
     * @param array $params
     * @throws FeatureDisabledException|Exception
     */
    private function handleUpdatingAvatar(array $params): void
    {
        if (! $this->usingDefault) {
            $this->attemptTransactionOrRollbackFile($this->uploadAvatar($params['image']))->removeOldAvatar();
        } else {
            $this->updateThread($this->theDefaultImage)->removeOldAvatar();
        }
    }

    /**
     * The avatar has been uploaded at this point, so if our
     * database actions fail, we want to remove the avatar
     * from storage and rethrow the exception.
     *
     * @param string $fileName
     * @return $this
     * @throws Exception
     */
    private function attemptTransactionOrRollbackFile(string $fileName): self
    {
        try {
            return $this->updateThread($fileName);
        } catch (Throwable $e) {
            $this->fileService
                ->setDisk($this->getThread()->getStorageDisk())
                ->destroy("{$this->getThread()->getAvatarDirectory()}/$fileName");

            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param UploadedFile $image
     * @return string|null
     * @throws FeatureDisabledException|FileServiceException
     */
    private function uploadAvatar(UploadedFile $image): string
    {
        $this->isThreadAvatarUploadEnabled();

        return $this->fileService
            ->setType('image')
            ->setDisk($this->getThread()->getStorageDisk())
            ->setDirectory($this->getThread()->getAvatarDirectory())
            ->upload($image);
    }

    /**
     * Remove the old avatar, if any.
     */
    private function removeOldAvatar(): void
    {
        if (! in_array($this->originalAvatar, Definitions::DefaultGroupAvatars)) {
            $this->fileService
                ->setDisk($this->getThread()->getStorageDisk())
                ->destroy("{$this->getThread()->getAvatarDirectory()}/$this->originalAvatar");
        }
    }

    /**
     * @param string $image
     * @return $this
     */
    private function updateThread(string $image): self
    {
        $this->getThread()->timestamps = false;

        $this->getThread()->update([
            'image' => $image,
        ]);

        return $this;
    }

    /**
     * Make the settings json resource
     */
    private function generateResource(): void
    {
        $this->setJsonResource(new ThreadSettingsResource(
            $this->getThread()
        ));
    }

    /**
     * @return array
     */
    private function generateBroadcastResource(): array
    {
        return (new ThreadSettingsBroadcastResource(
            $this->messenger->getProvider(),
            $this->getThread()
        ))->resolve();
    }

    /**
     * @return $this
     */
    private function fireBroadcast(): self
    {
        if ($this->shouldFireBroadcast()) {
            $this->broadcaster
                ->toPresence($this->getThread())
                ->with($this->generateBroadcastResource())
                ->broadcast(ThreadAvatarBroadcast::class);
        }

        return $this;
    }

    /**
     * @return void
     */
    private function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new ThreadAvatarEvent(
                $this->messenger->getProvider()->withoutRelations(),
                $this->getThread(true)
            ));
        }
    }

    /**
     * @return void
     * @throws FeatureDisabledException
     */
    private function isThreadAvatarUploadEnabled(): void
    {
        if (! $this->messenger->isThreadAvatarUploadEnabled()) {
            throw new FeatureDisabledException('Group avatar uploads are currently disabled.');
        }
    }
}
