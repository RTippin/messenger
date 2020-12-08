<?php

namespace RTippin\Messenger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RTippin\Messenger\Contracts\MessengerProvider;
use RTippin\Messenger\Traits\Uuids;

/**
 * App\Models\Mobile\MobileDevice.
 *
 * @property-read Model|\Eloquent $owner
 * @method static \Illuminate\Database\Eloquent\Builder|MobileDevice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MobileDevice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|MobileDevice query()
 * @mixin \Eloquent
 * @property string $id
 * @property string $owner_type
 * @property string $owner_id
 * @property string $device_id
 * @property string|null $oauth_access_token
 * @property string|null $device_token
 * @property string $device_os
 * @property string|null $device_name
 * @property string|null $voip_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|MobileDevice whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MobileDevice whereDeviceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MobileDevice whereDeviceName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MobileDevice whereDeviceOs($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MobileDevice whereDeviceToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MobileDevice whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MobileDevice whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MobileDevice whereOwnerType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MobileDevice whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|MobileDevice whereVoipToken($value)
 */
class MobileDevice extends Model
{
    use Uuids;

    /**
     * @var string
     */
    protected $table = 'mobile_devices';

    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var string
     */
    public $keyType = 'string';

    /**
     * @return MorphTo|MessengerProvider
     */
    public function owner()
    {
        return $this->morphTo();
    }
}
