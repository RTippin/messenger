<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use RTippin\Messenger\Database\Factories\SentFriendFactory;
use RTippin\Messenger\Facades\Messenger;

/**
 * Duplicate of PendingFriend. Used for ease of separation
 * between the type of "pending friend".
 *
 * @method static SentFriendFactory factory(...$parameters)
 */
class SentFriend extends PendingFriend
{
    /**
     * Compare the sender relation to the current
     * provider to see if they match.
     *
     * @return bool
     */
    public function isSenderCurrentProvider(): bool
    {
        if (! Messenger::isProviderSet()) {
            return false;
        }

        return (string) Messenger::getProvider()->getKey() === (string) $this->sender_id
            && Messenger::getProvider()->getMorphClass() === $this->sender_type;
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return Factory
     */
    protected static function newFactory(): Factory
    {
        return SentFriendFactory::new();
    }
}
