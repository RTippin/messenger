<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RTippin\Messenger\Contracts\EmojiInterface;
use RTippin\Messenger\Models\MessageReaction;

class MessageReactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MessageReaction::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'reaction' => app(EmojiInterface::class)->toShort($this->faker->emoji),
            'created_at' => now(),
        ];
    }
}
