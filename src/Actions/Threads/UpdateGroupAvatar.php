<?php

namespace RTippin\Messenger\Actions\Threads;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\UploadedFile;
use RTippin\Messenger\Actions\Base\BaseMessengerAction;
use RTippin\Messenger\Broadcasting\ThreadAvatarBroadcast;
use RTippin\Messenger\Contracts\BroadcastDriver;
use RTippin\Messenger\Definitions;
use RTippin\Messenger\Events\ThreadAvatarEvent;
use RTippin\Messenger\Http\Request\GroupAvatarRequest;
use RTippin\Messenger\Http\Resources\Broadcast\ThreadSettingsBroadcastResource;
use RTippin\Messenger\Http\Resources\ThreadSettingsResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Services\Messenger\FileService;

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
     * @var bool
     */
    private bool $avatarChanged = false;

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
     * @var Thread $thread $parameters[0]
     * @var GroupAvatarRequest $validated $parameters[1]
     * @return $this
     */
    public function execute(...$parameters): self
    {
        $this->setThread($parameters[0])
            ->setOriginalAvatar()
            ->determineAction($parameters[1])
            ->determineIfAvatarChanged()
            ->handleAction($parameters[1])
            ->removeOldAvatar()
            ->generateResource()
            ->fireBroadcast()
            ->fireEvents();

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
     * @return $this
     */
    private function determineAction(array $params): self
    {
        $this->usingDefault = array_key_exists('default', $params);

        if($this->usingDefault)
        {
            $this->theDefaultImage = $params['default'];
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function determineIfAvatarChanged(): self
    {
        if( ! $this->usingDefault
            || ($this->usingDefault
                && $this->getThread()->image !== $this->theDefaultImage))
        {
            $this->avatarChanged = true;
        }

        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    private function handleAction(array $params): self
    {
        if($this->avatarChanged)
        {
            if($this->usingDefault)
            {
                $this->updateThread($this->theDefaultImage);
            }
            else
            {
                $this->updateThread(
                    $this->uploadAvatar($params['image'])
                );
            }
        }
        else
        {
            $this->withoutDispatches();
        }

        return $this;
    }

    /**
     * @param UploadedFile $image
     * @return string|null
     */
    private function uploadAvatar(UploadedFile $image): ?string
    {
        return $this->fileService
            ->setType('image')
            ->setDisk($this->getThread()->getStorageDisk())
            ->setDirectory($this->getDirectory())
            ->upload($image)
            ->getName();
    }

    /**
     * @return string
     */
    private function getDirectory(): string
    {
        return "{$this->getThread()->getStorageDirectory()}/avatar";
    }

    /**
     * @return $this
     */
    private function removeOldAvatar(): self
    {
        if($this->avatarChanged
            && ! in_array($this->originalAvatar, Definitions::DefaultGroupAvatars))
        {
            $this->fileService
                ->setDisk($this->getThread()->getStorageDisk())
                ->destroy(
                    "{$this->getDirectory()}/{$this->originalAvatar}"
                );
        }

        return $this;
    }

    /**
     * @param string $image
     * @return $this
     */
    private function updateThread(string $image): self
    {
        $this->getThread()->timestamps = false;

        $this->getThread()->update([
            'image' => $image
        ]);

        return $this;
    }

    /**
     * Generate the thread settings resource
     *
     * @return $this
     */
    private function generateResource(): self
    {
        $this->setJsonResource(new ThreadSettingsResource(
            $this->getThread()
        ));

        return $this;
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
        if($this->shouldFireBroadcast())
        {
            $this->broadcaster
                ->toPresence($this->getThread())
                ->with($this->generateBroadcastResource())
                ->broadcast(ThreadAvatarBroadcast::class);
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function fireEvents(): self
    {
        if($this->shouldFireEvents())
        {
            $this->dispatcher->dispatch(new ThreadAvatarEvent(
                $this->messenger->getProvider()->withoutRelations(),
                $this->getThread(true)
            ));
        }

        return $this;
    }
}