<?php

namespace RTippin\Messenger\Actions\Bots;

use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Models\Bot;

class DestroyBotAvatar extends BotAvatarAction
{
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

        $this->generateResource()->fireEvents();

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
