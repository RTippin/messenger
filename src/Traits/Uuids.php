<?php

namespace RTippin\Messenger\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait Uuids
{
    /**
     * When the model is instantiated, set the primary
     * key type to string and disable incrementing.
     *
     * @return void
     */
    public function initializeUuids(): void
    {
        $this->setKeyType('string');
        $this->setIncrementing(false);
    }

    /**
     * On model creating, set the primary key to UUID.
     *
     * @return void
     */
    public static function bootUuids(): void
    {
        static::creating(function (Model $model) {
            $model->{$model->getKeyName()} = Str::orderedUuid()->toString();
        });
    }
}
