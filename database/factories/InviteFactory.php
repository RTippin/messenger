<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use RTippin\Messenger\Models\Invite;

/**
 * @method Invite create($attributes = [], ?Model $parent = null)
 * @method Invite make($attributes = [], ?Model $parent = null)
 */
class InviteFactory extends Factory
{
    use FactoryHelper;

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
     * Indicate thread is a group.
     *
     * @param $expires
     * @return $this
     */
    public function expires($expires): self
    {
        return $this->state(fn (array $attributes) => ['expires_at' => $expires]);
    }

    /**
     * Set code to TEST1234.
     *
     * @return $this
     */
    public function testing(): self
    {
        return $this->state(fn (array $attributes) => ['code' => 'TEST1234']);
    }

    /**
     * Set invite to all possible invalid states.
     *
     * @return $this
     */
    public function invalid(): self
    {
        return $this->state(fn (array $attributes) => [
            'max_use' => 5,
            'uses' => 5,
            'expires_at' => now()->subHour(),
        ]);
    }
}
