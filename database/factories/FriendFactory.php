<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RTippin\Messenger\Models\Friend;

/**
 * @method Friend create($attributes = [], ?Model $parent = null)
 * @method Friend make($attributes = [], ?Model $parent = null)
 */
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
     * Owner and party relations to add.
     *
     * @param $owner
     * @param $party
     * @return $this
     */
    public function providers($owner, $party): self
    {
        return $this->for($owner, 'owner')
            ->for($party, 'party');
    }
}
