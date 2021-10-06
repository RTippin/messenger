<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RTippin\Messenger\MessengerBots;
use RTippin\Messenger\Models\BotAction;

/**
 * @method BotAction create($attributes = [], ?Model $parent = null)
 * @method BotAction make($attributes = [], ?Model $parent = null)
 */
class BotActionFactory extends Factory
{
    use FactoryHelper;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = BotAction::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'handler' => 'ReplyBot',
            'match' => MessengerBots::MATCH_EXACT,
            'triggers' => '!hello',
            'enabled' => true,
            'payload' => null,
            'cooldown' => 0,
            'admin_only' => false,
        ];
    }

    /**
     * Specify bot action cooldown.
     *
     * @param  int  $cooldown
     * @return $this
     */
    public function cooldown(int $cooldown): self
    {
        return $this->state(fn (array $attributes) => ['cooldown' => $cooldown]);
    }

    /**
     * Indicate bot action is disabled.
     *
     * @return $this
     */
    public function disabled(): self
    {
        return $this->state(fn (array $attributes) => ['enabled' => false]);
    }

    /**
     * Set the action handler.
     *
     * @param  string  $handler
     * @return $this
     */
    public function handler(string $handler): self
    {
        return $this->state(fn (array $attributes) => ['handler' => $handler]);
    }

    /**
     * Set the action trigger.
     *
     * @param  string|null  $triggers
     * @return $this
     */
    public function triggers(?string $triggers): self
    {
        return $this->state(fn (array $attributes) => ['triggers' => $triggers]);
    }

    /**
     * Set the action payload.
     *
     * @param  string|array|null  $payload
     * @return $this
     */
    public function payload($payload = null): self
    {
        if (is_array($payload)) {
            $payload = json_encode($payload);
        }

        return $this->state(fn (array $attributes) => ['payload' => $payload]);
    }

    /**
     * Indicate the method used for matching trigger.
     *
     * @param  string  $match
     * @return self
     */
    public function match(string $match): self
    {
        return $this->state(fn (array $attributes) => ['match' => $match]);
    }

    /**
     * Indicate the trigger can only respond to admins.
     *
     * @return self
     */
    public function admin(): self
    {
        return $this->state(fn (array $attributes) => ['admin_only' => true]);
    }
}
