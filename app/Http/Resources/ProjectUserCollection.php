<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectUserCollection extends JsonResource
{
    /**
     * Transform the resource into an array.
     * @projectUser ProjectUser $ressource
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "role"=> $this->resource->role,
            "user_id"=> $this->resource->user_id,
        ];
    }
}
