<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * @user User $ressource
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'lastName' => $this->resource->last_name,
            "firstName" => $this->resource->first_name,
            'email' => $this->resource->email,
        ];
    }
}
