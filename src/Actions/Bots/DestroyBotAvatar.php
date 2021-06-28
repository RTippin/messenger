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
        $this->isBotAvatarEnabled();

        $this->setBot($parameters[0])
            ->removeOldIfExist()
            ->updateBotAvatar(null)
            ->generateResource();

        if ($this->getBot()->wasChanged()) {
            $this->fireEvents();
        }

        return $this;
    }
}
