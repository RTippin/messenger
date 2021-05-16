<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use RTippin\Messenger\Models\Participant;
use RTippin\Messenger\Support\Definitions;

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
        return Definitions::DefaultParticipant;
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
            return Definitions::DefaultAdminParticipant;
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
}
