<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RTippin\Messenger\Models\Bot;

/**
 * @method Bot create($attributes = [], ?Model $parent = null)
 */
class BotFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Bot::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'enabled' => true,
            'cooldown' => 0,
            'hide_actions' => false,
        ];
    }

    /**
     * Owner relation to add.
     *
     * @param $owner
     * @return $this
     */
    public function owner($owner): self
    {
        return $this->for($owner, 'owner');
    }

    /**
     * Specify bot cooldown.
     *
     * @param  int  $cooldown
     * @return $this
     */
    public function cooldown(int $cooldown): self
    {
        return $this->state(fn (array $attributes) => ['cooldown' => $cooldown]);
    }

    /**
     * Indicate bot is disabled.
     *
     * @return $this
     */
    public function disabled(): self
    {
        return $this->state(fn (array $attributes) => ['enabled' => false]);
    }

    /**
     * Indicate bot actions are hidden.
     *
     * @return $this
     */
    public function hideActions(): self
    {
        return $this->state(fn (array $attributes) => ['hide_actions' => true]);
    }

    /**
     * Indicate bot is soft-deleted.
     *
     * @return $this
     */
    public function trashed(): self
    {
        return $this->state(fn (array $attributes) => ['deleted_at' => now()]);
    }
}
