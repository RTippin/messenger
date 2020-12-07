<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use RTippin\Messenger\Models\Messenger;

class MessengerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     * @noinspection PhpMissingParamTypeInspection
     */
    public function toArray($request): array
    {
        /** @var Messenger $messenger */

        $messenger = $this->resource;

        return [
            'owner' => new ProviderResource(messenger()->getProvider()),
            $this->merge($messenger)
        ];
    }
}
