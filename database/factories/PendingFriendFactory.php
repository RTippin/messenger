<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RTippin\Messenger\Models\PendingFriend;

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
     * @return Factory
     */
    public function providers($sender, $recipient): Factory
    {
        return $this->for($sender, 'sender')
            ->for($recipient, 'recipient');
    }
}
