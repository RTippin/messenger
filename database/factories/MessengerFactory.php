<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use RTippin\Messenger\Models\Messenger;

/**
 * @method Messenger create($attributes = [], ?Model $parent = null)
 * @method Messenger make($attributes = [], ?Model $parent = null)
 */
class MessengerFactory extends Factory
{
    use FactoryHelper;

    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Messenger::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'message_popups' => true,
            'message_sound' => true,
            'call_ringtone_sound' => true,
            'notify_sound' => true,
            'online_status' => 1,
            'dark_mode' => true,
            'ip' => null,
            'timezone' => null,
        ];
    }
}
