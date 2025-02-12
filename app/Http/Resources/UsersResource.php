<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsersResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *@user User $ressource
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'lastName' => $this->resource->last_name,
            "firstName" => $this->resource->first_name,
            "role" => $this->resource->pivot->role,
            "pictureUrl" => $this->resource->profil->picture??null,
        ];
    }
}
