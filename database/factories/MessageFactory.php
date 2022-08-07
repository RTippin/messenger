<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RTippin\Messenger\Facades\MessengerTypes;
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
            'type' => MessengerTypes::code('MESSAGE'),
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
        return $this->state(['body' => $body]);
    }

    /**
     * Indicate message is an image.
     *
     * @return $this
     */
    public function image(): self
    {
        return $this->state([
            'type' => MessengerTypes::code('IMAGE_MESSAGE'),
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
        return $this->state([
            'type' => MessengerTypes::code('DOCUMENT_MESSAGE'),
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
        return $this->state([
            'type' => MessengerTypes::code('AUDIO_MESSAGE'),
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
        return $this->state([
            'type' => MessengerTypes::code('VIDEO_MESSAGE'),
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
        return $this->state([
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
        return $this->state(['edited' => true]);
    }

    /**
     * Indicate message is an image.
     *
     * @return $this
     */
    public function reacted(): self
    {
        return $this->state(['reacted' => true]);
    }

    /**
     * Set the messages reply to ID.
     *
     * @param  string  $messageId
     * @return $this
     */
    public function reply(string $messageId): self
    {
        return $this->state(['reply_to_id' => $messageId]);
    }
}
