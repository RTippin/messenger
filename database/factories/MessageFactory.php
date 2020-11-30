<?php

namespace RTippin\Messenger\Database\Factories;

use RTippin\Messenger\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Message::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'type' => 0,
            'body' => $this->faker->realText(rand(10, 200), rand(1,4))
        ];
    }
}
