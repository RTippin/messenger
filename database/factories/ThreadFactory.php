<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RTippin\Messenger\Models\Thread;

class ThreadFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Thread::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'type' => 2,
            'subject' => $this->faker->company,
            'image' => rand(1, 5).'.png',
            'add_participants' => 1,
            'invitations' => 1,
            'calling' => 1,
            'knocks' => 1,
            'lockout' => 0,
        ];
    }
}
