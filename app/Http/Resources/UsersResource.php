<?php

namespace App\Http\Resources;

use App\Action\Translate;
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
        $translatedRole = (new Translate())($this->resource->pivot->role);
        return [
            'lastName' => $this->resource->last_name,
            "firstName" => $this->resource->first_name,
            "role" => $translatedRole,
            "email" => $this->resource->email,
            "pictureUrl" => $this->resource->profil->picture??null,
        ];
    }
}
