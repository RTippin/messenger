<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RTippin\Messenger\Models\CallParticipant;

/**
 * @method CallParticipant create($attributes = [], ?Model $parent = null)
 * @method CallParticipant make($attributes = [], ?Model $parent = null)
 */
class CallParticipantFactory extends Factory
{
    use FactoryHelper;

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
     * @return $this
     */
    public function left(): self
    {
        return $this->state(fn (array $attributes) => ['left_call' => now()]);
    }

    /**
     * Indicate participant was kicked.
     *
     * @return $this
     */
    public function kicked(): self
    {
        return $this->state(fn (array $attributes) => ['kicked' => true]);
    }
}
