<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RTippin\Messenger\Models\PendingFriend;

/**
 * @method PendingFriend create($attributes = [], ?Model $parent = null)
 * @method PendingFriend make($attributes = [], ?Model $parent = null)
 */
class PendingFriendFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PendingFriend::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [];
    }

    /**
     * Sender and recipient relations to add.
     *
     * @param $sender
     * @param $recipient
     * @return $this
     */
    public function providers($sender, $recipient): self
    {
        return $this->for($sender, 'sender')
            ->for($recipient, 'recipient');
    }
}
