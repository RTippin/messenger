<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RTippin\Messenger\Models\Participant;

/**
 * @method Participant create($attributes = [], ?Model $parent = null)
 */
class ParticipantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Participant::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return array_merge(Participant::DefaultPermissions, [
            'muted' => false,
        ]);
    }

    /**
     * Owner relation to add.
     *
     * @param $owner
     * @return $this
     */
    public function owner($owner): self
    {
        return $this->for($owner, 'owner');
    }

    /**
     * Indicate participant is admin.
     *
     * @return $this
     */
    public function admin(): self
    {
        return $this->state(fn (array $attributes) => Participant::AdminPermissions);
    }

    /**
     * Indicate participant is pending.
     *
     * @return $this
     */
    public function pending(): self
    {
        return $this->state(fn (array $attributes) => ['pending' => true]);
    }

    /**
     * Indicate participant is muted.
     *
     * @return $this
     */
    public function muted(): self
    {
        return $this->state(fn (array $attributes) => ['muted' => true]);
    }

    /**
     * Indicate participant is read.
     *
     * @return $this
     */
    public function read(): self
    {
        return $this->state(fn (array $attributes) => ['last_read' => now()]);
    }

    /**
     * Indicate participant is soft deleted.
     *
     * @return $this
     */
    public function trashed(): self
    {
        return $this->state(fn (array $attributes) => ['deleted_at' => now()]);
    }
}
