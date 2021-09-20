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

    /**
     * Return the private channel name.
     *
     * @return string
     */
    public function getOwnerPrivateChannel(): string;

    /**
     * Compare the morph relation on the given model
     * to the current provider to see if they match.
     *
     * @return bool
     */
    public function isOwnedByCurrentProvider(): bool;
}
