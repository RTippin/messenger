<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RTippin\Messenger\Models\BotAction;

class BotActionFactory extends Factory
{
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
            'match' => 'exact',
            'triggers' => '!hello',
            'enabled' => true,
            'payload' => null,
            'cooldown' => 0,
            'admin_only' => false,
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
     * Indicate thread is locked.
     *
     * @return Factory
     */
    public function disabled(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'enabled' => false,
            ];
        });
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
     * @param string|null $triggers
     * @return Factory
     */
    public function triggers(?string $triggers): Factory
    {
        return $this->state(function (array $attributes) use ($triggers) {
            return [
                'triggers' => $triggers,
            ];
        });
    }

    /**
     * Set the actions payload.
     *
     * @param string|array|null $payload
     * @return Factory
     */
    public function payload($payload = null): Factory
    {
        if(is_array($payload)){
            $payload = json_encode($payload);
        }

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
                'match' => $match,
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
                'admin_only' => true,
            ];
        });
    }
}
