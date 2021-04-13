<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RTippin\Messenger\Models\Messenger;

class MessengerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Messenger::class;

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
     * Owner relation to add.
     *
     * @param $provider
     * @return Factory
     */
    public function provider($provider): Factory
    {
        return $this->for($provider, 'owner');
    }
}
