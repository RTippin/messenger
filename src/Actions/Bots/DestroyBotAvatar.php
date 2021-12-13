<?php

namespace RTippin\Messenger\Actions\Bots;

use RTippin\Messenger\Exceptions\FeatureDisabledException;
use RTippin\Messenger\Models\Bot;

class DestroyBotAvatar extends BotAvatarAction
{
    /**
     * @param  Bot  $bot
     * @return $this
     *
     * @throws FeatureDisabledException
     */
    public function execute(Bot $bot): self
    {
        $this->bailIfDisabled();

        $this->setBot($bot)
            ->removeOldIfExist()
            ->updateBotAvatar(null)
            ->generateResource();

        if ($this->getBot()->wasChanged()) {
            $this->clearActionsCache()->fireEvents();
        }

        return $this;
    }
}
