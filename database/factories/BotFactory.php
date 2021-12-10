<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RTippin\Messenger\Models\Bot;

/**
 * @method Bot create($attributes = [], ?Model $parent = null)
 * @method Bot make($attributes = [], ?Model $parent = null)
 */
class BotFactory extends Factory
{
    use FactoryHelper;

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
     * Set the bots name.
     *
     * @return $this
     */
    public function name(string $name): self
    {
        return $this->state(fn (array $attributes) => ['name' => $name]);
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
}
