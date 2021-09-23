<?php

namespace RTippin\Messenger\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;
use RTippin\Messenger\Models\Bot;

/**
 * @property string|int $owner_id
 * @property string $owner_type
 * @property-read Model|MessengerProvider $owner
 * @mixin Model|\Eloquent
 */
trait HasOwner
{
    /**
     * @inheritDoc
     */
    public function owner(): MorphTo
    {
        return $this->morphTo()->withDefault(
            fn (Model $owner) => $owner instanceof Bot
                ? Messenger::getGhostBot()
                : Messenger::getGhostProvider()
        );
    }

    /**
     * @inheritDoc
     */
    public function getOwnerPrivateChannel(): string
    {
        if (Messenger::isValidMessengerProvider($this->owner_type)) {
            return Messenger::findProviderAlias($this->owner_type).'.'.$this->owner_id;
        }

        return '';
    }

    /**
     * @inheritDoc
     */
    public function isOwnedByCurrentProvider(): bool
    {
        if (! Messenger::isProviderSet()) {
            return false;
        }

        return (string) Messenger::getProvider()->getKey() === (string) $this->owner_id
            && Messenger::getProvider()->getMorphClass() === $this->owner_type;
    }
}
