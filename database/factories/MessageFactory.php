<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RTippin\Messenger\Models\Message;

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
    public function definition(): array
    {
        return [
            'type' => 0,
            'body' => $this->faker->realText(rand(10, 200), rand(1, 4)),
            'edited' => false,
            'reacted' => false,
            'embeds' => true,
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
     * Indicate message is soft deleted.
     *
     * @return Factory
     */
    public function trashed(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'deleted_at' => now(),
            ];
        });
    }

    /**
     * Indicate message is an image.
     *
     * @return Factory
     */
    public function image(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 1,
                'body' => 'picture.jpg',
            ];
        });
    }

    /**
     * Indicate message is a document.
     *
     * @return Factory
     */
    public function document(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 2,
                'body' => 'document.pdf',
            ];
        });
    }

    /**
     * Indicate message is audio.
     *
     * @return Factory
     */
    public function audio(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 3,
                'body' => 'sound.mp3',
            ];
        });
    }

    /**
     * Indicate message is a system message.
     *
     * @param int|null $type
     * @return Factory
     */
    public function system(?int $type = null): Factory
    {
        return $this->state(function (array $attributes) use ($type) {
            return [
                'type' => $type ?? rand(90, 103),
                'body' => 'This is a system message.',
            ];
        });
    }

    /**
     * Indicate message is edited.
     *
     * @return Factory
     */
    public function edited(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'edited' => true,
            ];
        });
    }

    /**
     * Indicate message is an image.
     *
     * @return Factory
     */
    public function reacted(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'reacted' => true,
            ];
        });
    }
}
