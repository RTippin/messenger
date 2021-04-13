<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RTippin\Messenger\Models\CallParticipant;

class CallParticipantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CallParticipant::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'kicked' => false,
            'left_call' => null,
        ];
    }

    /**
     * Indicate participant has left.
     *
     * @return Factory
     */
    public function left(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'left_call' => now(),
            ];
        });
    }

    /**
     * Indicate participant was kicked.
     *
     * @return Factory
     */
    public function kicked(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'kicked' => true,
            ];
        });
    }
}
