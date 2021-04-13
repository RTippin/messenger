<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use RTippin\Messenger\Database\Factories\SentFriendFactory;

/**
 * App\Models\Friend\SentFriend.
 *
 * Duplicate of PendingFriend. Used for ease of separation
 * between the type of "pending friend".
 */
class SentFriend extends PendingFriend
{
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
