<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RTippin\Messenger\Models\MessageEdit;

/**
 * @method MessageEdit create($attributes = [], ?Model $parent = null)
 * @method MessageEdit make($attributes = [], ?Model $parent = null)
 */
class MessageEditFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MessageEdit::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'body' => $this->faker->realText(rand(10, 200), rand(1, 4)),
            'edited_at' => now(),
        ];
    }
}
