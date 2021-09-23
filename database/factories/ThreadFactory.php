<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RTippin\Messenger\Models\Thread;

/**
 * @method Thread create($attributes = [], ?Model $parent = null)
 */
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
     * @return $this
     */
    public function group(): self
    {
        return $this->state(fn (array $attributes) => [
                'type' => Thread::GROUP,
                'subject' => $this->faker->company,
                'image' => null,
                'add_participants' => true,
                'invitations' => true,
                'chat_bots' => true,
            ]
        );
    }

    /**
     * Indicate thread is locked.
     *
     * @return $this
     */
    public function locked(): self
    {
        return $this->state(fn (array $attributes) => ['lockout' => true]);
    }

    /**
     * Indicate thread is soft-deleted.
     *
     * @return $this
     */
    public function trashed(): self
    {
        return $this->state(fn (array $attributes) => ['deleted_at' => now()]);
    }
}
