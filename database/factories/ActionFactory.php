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
            'admin_only' => false,
            'match_method' => 'exact',
            'trigger' => '!hello',
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
     * @param string $payload
     * @return Factory
     */
    public function payload(string $payload): Factory
    {
        return $this->state(function (array $attributes) use ($payload) {
            return [
                'payload' => $payload,
            ];
        });
    }

    /**
     * Indicate the method used for matching trigger.
     *
     * @param string $match
     * @return Factory
     */
    public function match(string $match): Factory
    {
        return $this->state(function (array $attributes) use ($match) {
            return [
                'match_method' => $match,
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
