<?php

namespace RTippin\Messenger\Actions\Bots;

use Illuminate\Contracts\Events\Dispatcher;
use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Models\Bot;
use RTippin\Messenger\Services\FileService;

class DestroyBotAvatar extends BotAvatarAction
{
    /**
     * DestroyBotAvatar constructor.
     *
     * @param Messenger $messenger
     * @param FileService $fileService
     * @param Dispatcher $dispatcher
     */
    public function __construct(Messenger $messenger,
                                FileService $fileService,
                                Dispatcher $dispatcher)
    {
        parent::__construct(
            $messenger,
            $fileService,
            $dispatcher
        );
    }

    /**
     * @param mixed ...$parameters
     * @param Bot[0]
     * @return $this
     * @throws FeatureDisabledException
     */
    public function execute(...$parameters): self
    {
        $this->isBotAvatarRemovalEnabled();

        $this->setBot($parameters[0])
            ->removeOldIfExist()
            ->updateBotAvatar(null);

        $this->fireEvents();

        return $this;
    }

    /**
     * @throws FeatureDisabledException
     */
    private function isBotAvatarRemovalEnabled(): void
    {
        if (! $this->messenger->isBotsEnabled()) {
            throw new FeatureDisabledException('Bot Avatar removal is currently disabled.');
        }
    }
}
