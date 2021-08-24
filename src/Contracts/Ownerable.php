<?php

namespace RTippin\Messenger\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string|int $owner_id
 * @property string $owner_type
 * @property-read Model|MessengerProvider $owner
 * @mixin Model|\Eloquent
 */
interface Ownerable
{
    /**
     * @return MorphTo|MessengerProvider
     */
    public function owner(): MorphTo;
}
