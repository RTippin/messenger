<?php

namespace RTippin\Messenger\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphTo;

interface Ownerable
{
    /**
     * @return MorphTo|MessengerProvider
     */
    public function owner(): MorphTo;

    /**
     * Return the private channel name.
     *
     * @return string
     */
    public function getOwnerPrivateChannel(): string;
}
