<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Facades\Messenger as MessengerFacade;
use RTippin\Messenger\Models\Messenger;

class MessengerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Messenger $messenger */
        $messenger = $this->resource;

        return [
            'owner' => (new ProviderResource(MessengerFacade::getProvider()))->resolve(),
            $this->merge($messenger),
        ];
    }
}
