<?php

namespace RTippin\Messenger\Contracts;

use RTippin\Messenger\Models\Call;
use RTippin\Messenger\Models\Thread;

interface VideoDriver
{
    /**
     * Setup the video room for the call/thread. Set the values for
     * the getters listed below and return true/false if setup was
     * successful. We want access to both the thread and call to
     * decide what parameters we may want to use for setting up
     * a video room.
     *
     * @param  Thread  $thread
     * @param  Call  $call
     * @return bool
     */
    public function create(Thread $thread, Call $call): bool;

    /**
     * Teardown the video room for the call/thread. Return true/false
     * to let us know if it was successful. We only need the call
     * model as we should have saved any information needed for
     * teardown there.
     *
     * @param  Call  $call
     * @return mixed
     */
    public function destroy(Call $call): bool;

    /**
     * Called after a successful create.
     *
     * @return string|null
     */
    public function getRoomId(): ?string;

    /**
     * Called after a successful create.
     *
     * @return string|null
     */
    public function getRoomPin(): ?string;

    /**
     * Called after a successful create.
     *
     * @return string|null
     */
    public function getRoomSecret(): ?string;

    /**
     * Called after a successful create.
     *
     * @return mixed
     */
    public function getExtraPayload();
}
