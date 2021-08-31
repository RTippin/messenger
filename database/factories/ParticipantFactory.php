<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RTippin\Messenger\Models\Participant;

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
     * @return Factory
     */
    public function owner($owner): Factory
    {
        return $this->for($owner, 'owner');
    }

    /**
     * Indicate participant is admin.
     *
     * @return Factory
     */
    public function admin(): Factory
    {
        return $this->state(function (array $attributes) {
            return Participant::AdminPermissions;
        });
    }

    /**
     * Indicate participant is pending.
     *
     * @return Factory
     */
    public function pending(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'pending' => true,
            ];
        });
    }

    /**
     * Indicate participant is muted.
     *
     * @return Factory
     */
    public function muted(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'muted' => true,
            ];
        });
    }

    /**
     * Indicate participant is read.
     *
     * @return Factory
     */
    public function read(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'last_read' => now(),
            ];
        });
    }

    /**
     * Indicate participant is soft deleted.
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
}
