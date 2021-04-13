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
}
