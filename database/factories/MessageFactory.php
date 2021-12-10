<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RTippin\Messenger\Models\Message;

/**
 * @method Message create($attributes = [], ?Model $parent = null)
 * @method Message make($attributes = [], ?Model $parent = null)
 */
class MessageFactory extends Factory
{
    use FactoryHelper;

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
            'type' => Message::MESSAGE,
            'body' => $this->faker->realText(rand(10, 200), rand(1, 4)),
            'edited' => false,
            'reacted' => false,
            'embeds' => true,
        ];
    }

    /**
     * Set the messages body.
     *
     * @return $this
     */
    public function body(?string $body): self
    {
        return $this->state(fn (array $attributes) => ['body' => $body]);
    }

    /**
     * Indicate message is an image.
     *
     * @return $this
     */
    public function image(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => Message::IMAGE_MESSAGE,
            'body' => 'picture.jpg',
        ]);
    }

    /**
     * Indicate message is a document.
     *
     * @return $this
     */
    public function document(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => Message::DOCUMENT_MESSAGE,
            'body' => 'document.pdf',
        ]);
    }

    /**
     * Indicate message is audio.
     *
     * @return $this
     */
    public function audio(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => Message::AUDIO_MESSAGE,
            'body' => 'sound.mp3',
        ]);
    }

    /**
     * Indicate message is audio.
     *
     * @return $this
     */
    public function video(): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => Message::VIDEO_MESSAGE,
            'body' => 'video.mov',
        ]);
    }

    /**
     * Indicate message is a system message.
     *
     * @param  int|null  $type
     * @return $this
     */
    public function system(?int $type = null): self
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type ?: rand(90, 104),
            'body' => 'This is a system message.',
        ]);
    }

    /**
     * Indicate message is edited.
     *
     * @return $this
     */
    public function edited(): self
    {
        return $this->state(fn (array $attributes) => ['edited' => true]);
    }

    /**
     * Indicate message is an image.
     *
     * @return $this
     */
    public function reacted(): self
    {
        return $this->state(fn (array $attributes) => ['reacted' => true]);
    }

    /**
     * Set the messages reply to ID.
     *
     * @param  string  $messageId
     * @return $this
     */
    public function reply(string $messageId): self
    {
        return $this->state(fn (array $attributes) => ['reply_to_id' => $messageId]);
    }
}
