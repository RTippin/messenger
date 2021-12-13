<?php

namespace RTippin\Messenger\Actions\Messenger;

use RTippin\Messenger\Exceptions\FeatureDisabledException;

class DestroyMessengerAvatar extends MessengerAvatarAction
{
    /**
     * @return $this
     *
     * @throws FeatureDisabledException
     */
    public function execute(): self
    {
        $this->bailIfDisabled();

        $this->removeOldIfExist()->updateProviderAvatar(null);

        return $this;
    }

    /**
     * @throws FeatureDisabledException
     */
    private function bailIfDisabled(): void
    {
        if (! $this->messenger->isProviderAvatarEnabled()) {
            throw new FeatureDisabledException('Avatar removal is currently disabled.');
        }
    }
}
