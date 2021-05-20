<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use RTippin\Messenger\Models\Invite;

class InviteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Invite::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'code' => Str::random(6),
            'max_use' => rand(0, 10),
            'uses' => 0,
            'expires_at' => null,
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
     * Indicate thread is a group.
     *
     * @param $expires
     * @return Factory
     */
    public function expires($expires): Factory
    {
        return $this->state(function (array $attributes) use ($expires) {
            return [
                'expires_at' => $expires,
            ];
        });
    }

    /**
     * Set code to TEST1234.
     *
     * @return Factory
     */
    public function testing(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'code' => 'TEST1234',
            ];
        });
    }

    /**
     * Set invite to all possible invalid states.
     *
     * @return Factory
     */
    public function invalid(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'max_use' => 5,
                'uses' => 5,
                'expires_at' => now()->subHour(),
            ];
        });
    }

    /**
     * Indicate invite is soft deleted.
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
