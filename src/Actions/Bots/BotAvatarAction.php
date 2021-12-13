<?php

namespace RTippin\Messenger\Actions\Bots;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Actions\BaseMessengerAction;
use RTippin\Messenger\Events\BotAvatarEvent;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Http\Resources\BotResource;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\BotAction;
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
     * @param  Messenger  $messenger
     * @param  FileService  $fileService
     * @param  Dispatcher  $dispatcher
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
     * @throws FeatureDisabledException
     */
    protected function bailIfDisabled(): void
    {
        if (! $this->messenger->isBotsEnabled()
            || ! $this->messenger->isBotAvatarEnabled()) {
            throw new FeatureDisabledException('Bot avatars are currently disabled.');
        }
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
     * @param  string|null  $avatar
     * @return $this
     */
    protected function updateBotAvatar(?string $avatar): self
    {
        $this->getBot()->update([
            'avatar' => $avatar,
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    protected function clearActionsCache(): self
    {
        if ($this->shouldExecuteChains()) {
            BotAction::clearActionsCacheForThread($this->getBot()->thread_id);
        }

        return $this;
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
