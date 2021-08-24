<?php

namespace RTippin\Messenger\Contracts;

interface HasPresenceChannel
{
    /**
     * Return the presence channel name.
     *
     * @return string
     */
    public function getPresenceChannel(): string;
}
