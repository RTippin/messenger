<?php

namespace RTippin\Messenger\Http\Controllers\Actions;

use RTippin\Messenger\Actions\Messenger\OnlineStatus;
use RTippin\Messenger\Http\Request\StatusHeartbeatRequest;
use RTippin\Messenger\Http\Resources\ProviderStatusResource;
use RTippin\Messenger\Messenger;

class StatusHeartbeat
{
    /**
     * Update providers online cache state.
     *
     * @param  StatusHeartbeatRequest  $request
     * @param  Messenger  $messenger
     * @param  OnlineStatus  $onlineStatus
     * @return ProviderStatusResource
     */
    public function __invoke(StatusHeartbeatRequest $request,
                             Messenger $messenger,
                             OnlineStatus $onlineStatus): ProviderStatusResource
    {
        $onlineStatus->execute($request->input('away') ?? false);

        return new ProviderStatusResource(
            $messenger->getProvider()
        );
    }
}
