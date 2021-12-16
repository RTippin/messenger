<?php

namespace RTippin\Messenger\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RTippin\Messenger\Models\Messenger;

/**
 * @method CompanyModel create($attributes = [], ?Model $parent = null)
 * @method CompanyModel make($attributes = [], ?Model $parent = null)
 */
class CompanyModelFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CompanyModel::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'company_name' => $this->faker->company,
            'company_email' => $this->faker->unique()->safeEmail,
            'password' => 'secret',
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure(): self
    {
        return $this->afterCreating(function (CompanyModel $company) {
            Messenger::factory()->owner($company)->create();
        });
    }
}
