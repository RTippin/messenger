<?php

namespace RTippin\Messenger\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BotHandlerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'alias' => $this->resource['alias'],
            'description' => $this->resource['description'],
            'name' => $this->resource['name'],
            'unique' => $this->resource['unique'] ?? false,
            'authorize' => $this->resource['authorize'] ?? false,
            'triggers' => $this->resource['triggers'] ?? null,
            'match' => $this->resource['match'] ?? null,
        ];
    }
}
