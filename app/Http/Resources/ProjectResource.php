<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *@project Project $ressource
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"=>$this->resource->id,
            "title"=>$this->resource->title,
            "backgroundColor"=>$this->resource->background_color,
            "url"=>$this->resource->background_image,
        ];
    }
}
