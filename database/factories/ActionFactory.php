<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RTippin\Messenger\Models\Action;

class ActionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Action::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'handler' => 'ReplyBot',
            'admin_trigger' => false,
            'exact_match' => false,
            'trigger' => '!hello',
            'payload' => '{"reply":"world"}',
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
     * Set the actions handler.
     *
     * @param string $handler
     * @return Factory
     */
    public function handler(string $handler): Factory
    {
        return $this->state(function (array $attributes) use ($handler) {
            return [
                'handler' => $handler,
            ];
        });
    }

    /**
     * Set the actions trigger.
     *
     * @param string|null $trigger
     * @return Factory
     */
    public function trigger(?string $trigger): Factory
    {
        return $this->state(function (array $attributes) use ($trigger) {
            return [
                'trigger' => $trigger,
            ];
        });
    }

    /**
     * Set the actions payload.
     *
     * @param string|null $payload
     * @return Factory
     */
    public function payload(?string $payload): Factory
    {
        return $this->state(function (array $attributes) use ($payload) {
            return [
                'payload' => $payload,
            ];
        });
    }

    /**
     * Indicate the trigger should be exact.
     *
     * @return Factory
     */
    public function exact(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'exact_match' => true,
            ];
        });
    }

    /**
     * Indicate the trigger can only respond to admins.
     *
     * @return Factory
     */
    public function admin(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'admin_trigger' => true,
            ];
        });
    }
}
