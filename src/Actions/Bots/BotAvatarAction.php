<?php

namespace RTippin\Messenger\Actions\Bots;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Events\BotAvatarEvent;
use RTippin\Messenger\Http\Resources\BotResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Services\FileService;

abstract class BotAvatarAction extends BaseMessengerAction
{
    /**
     * @var FileService
     */
    protected FileService $fileService;

    /**
     * @var Messenger
     */
    protected Messenger $messenger;

    /**
     * @var Dispatcher
     */
    protected Dispatcher $dispatcher;

    /**
     * BotAvatarAction constructor.
     *
     * @param Messenger $messenger
     * @param FileService $fileService
     * @param Dispatcher $dispatcher
     */
    public function __construct(Messenger $messenger,
                                FileService $fileService,
                                Dispatcher $dispatcher)
    {
        $this->fileService = $fileService;
        $this->messenger = $messenger;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @return $this
     */
    protected function removeOldIfExist(): self
    {
        if (! is_null($this->getBot()->avatar)) {
            $this->fileService
                ->setDisk($this->getBot()->getStorageDisk())
                ->destroy($this->getBot()->getAvatarPath());
        }

        return $this;
    }

    /**
     * @param string|null $avatar
     */
    protected function updateBotAvatar(?string $avatar): void
    {
        $this->getBot()->update([
            'avatar' => $avatar,
        ]);
    }

    /**
     * @return $this
     */
    protected function generateResource(): self
    {
        $this->setJsonResource(new BotResource(
            $this->getBot()
        ));

        return $this;
    }

    /**
     * @return void
     */
    protected function fireEvents(): void
    {
        if ($this->shouldFireEvents()) {
            $this->dispatcher->dispatch(new BotAvatarEvent(
                $this->messenger->getProvider(true),
                $this->getBot(true)
            ));
        }
    }
}
