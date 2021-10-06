<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RTippin\Messenger\Models\Call;

/**
 * @method Call create($attributes = [], ?Model $parent = null)
 * @method Call make($attributes = [], ?Model $parent = null)
 */
class CallFactory extends Factory
{
    use FactoryHelper;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Call::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'type' => Call::VIDEO,
            'call_ended' => null,
            'setup_complete' => false,
            'teardown_complete' => false,
            'room_id' => null,
            'room_pin' => null,
            'room_secret' => null,
            'payload' => null,
        ];
    }

    /**
     * Indicate the call is setup.
     *
     * @return $this
     */
    public function setup(): self
    {
        return $this->state(fn (array $attributes) => [
            'setup_complete' => true,
            'room_id' => '123456789',
            'room_pin' => 'PIN',
            'room_secret' => 'SECRET',
            'payload' => 'PAYLOAD',
        ]);
    }

    /**
     * Indicate call has ended.
     *
     * @return $this
     */
    public function ended(): self
    {
        return $this->state(fn (array $attributes) => [
            'setup_complete' => true,
            'teardown_complete' => true,
            'call_ended' => now(),
            'room_id' => '123456789',
            'room_pin' => 'PIN',
            'room_secret' => 'SECRET',
            'payload' => 'PAYLOAD',
        ]);
    }
}
