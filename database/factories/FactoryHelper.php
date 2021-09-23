<?php

namespace RTippin\Messenger\Database\Factories;

use Illuminate\Support\Carbon;

trait FactoryHelper
{
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
     * Indicate model is soft-deleted.
     *
     * @param  Carbon|null  $trashedAt
     * @return $this
     */
    public function trashed(?Carbon $trashedAt = null): self
    {
        return $this->state(fn (array $attributes) => ['deleted_at' => $trashedAt ?: now()]);
    }
}
