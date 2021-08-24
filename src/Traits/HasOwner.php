<?php

namespace RTippin\Messenger\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Facades\Messenger;

/**
 * @property string|int $owner_id
 * @property string $owner_type
 * @property-read Model|MessengerProvider $owner
 * @mixin Model|\Eloquent
 */
trait HasOwner
{
    /**
     * @return MorphTo|MessengerProvider
     */
    public function owner(): MorphTo
    {
        return $this->morphTo()->withDefault(function () {
            return Messenger::getGhostProvider();
        });
    }
}
