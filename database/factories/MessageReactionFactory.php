<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RTippin\Messenger\Contracts\EmojiInterface;
use RTippin\Messenger\Models\MessageReaction;

/**
 * @method MessageReaction create($attributes = [], ?Model $parent = null)
 * @method MessageReaction make($attributes = [], ?Model $parent = null)
 */
class MessageReactionFactory extends Factory
{
    use FactoryHelper;

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

    /**
     * Set the message reaction.
     *
     * @param  string  $reaction
     * @return $this
     */
    public function reaction(string $reaction): self
    {
        return $this->state(fn (array $attributes) => ['reaction' => $reaction]);
    }
}
