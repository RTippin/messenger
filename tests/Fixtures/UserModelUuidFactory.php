<?php

namespace RTippin\Messenger\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\Factory;
use RTippin\Messenger\Models\Messenger;

class UserModelUuidFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserModelUuid::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'secret',
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure(): self
    {
        return $this->afterCreating(function (UserModelUuid $user) {
            Messenger::factory()->owner($user)->create();
        });
    }
}
