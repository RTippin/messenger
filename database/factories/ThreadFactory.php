<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RTippin\Messenger\Models\Thread;

/**
 * @method Thread create($attributes = [], ?Model $parent = null)
 * @method Thread make($attributes = [], ?Model $parent = null)
 */
class ThreadFactory extends Factory
{
    use FactoryHelper;

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
        ]);
    }

    /**
     * Set the threads subject.
     *
     * @return $this
     */
    public function subject(string $subject): self
    {
        return $this->state(fn (array $attributes) => ['subject' => $subject]);
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
}
