<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RTippin\Messenger\Models\Call;

class CallFactory extends Factory
{
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
            'type' => 1,
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
     * Owner relation to add.
     *
     * @param $owner
     * @return Factory
     */
    public function owner($owner): Factory
    {
        return $this->for($owner, 'owner');
    }

    /**
     * Indicate call is setup.
     *
     * @return Factory
     */
    public function setup(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'setup_complete' => true,
                'room_id' => '123456789',
                'room_pin' => 'PIN',
                'room_secret' => 'SECRET',
                'payload' => 'PAYLOAD',
            ];
        });
    }

    /**
     * Indicate call has ended.
     *
     * @return Factory
     */
    public function ended(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'setup_complete' => true,
                'teardown_complete' => true,
                'call_ended' => now(),
                'room_id' => '123456789',
                'room_pin' => 'PIN',
                'room_secret' => 'SECRET',
                'payload' => 'PAYLOAD',
            ];
        });
    }
}
