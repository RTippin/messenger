<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RTippin\Messenger\Models\Thread;

class ThreadFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Thread::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return Thread::DefaultSettings;
    }

    /**
     * Indicate thread is a group.
     *
     * @return Factory
     */
    public function group(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => Thread::GROUP,
                'subject' => $this->faker->company,
                'image' => null,
                'add_participants' => true,
                'invitations' => true,
                'chat_bots' => true,
            ];
        });
    }

    /**
     * Indicate thread is locked.
     *
     * @return Factory
     */
    public function locked(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'lockout' => true,
            ];
        });
    }

    /**
     * Indicate thread is soft deleted.
     *
     * @return Factory
     */
    public function trashed(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'deleted_at' => now(),
            ];
        });
    }
}
