<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RTippin\Messenger\Models\Friend;

class FriendFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Friend::class;

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
     * Indicate participant has left.
     *
     * @param $owner
     * @param $party
     * @return Factory
     */
    public function providers($owner, $party): Factory
    {
        return $this->for($owner, 'owner')
            ->for($party, 'party');
    }
}
