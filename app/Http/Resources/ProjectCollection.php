<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectCollection extends JsonResource
{
    /**
     * Transform the resource into an array.
     * @project Project $ressource
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
       return [
         "id" => $this->resource->id,
         "background_color" => $this->resource->background_color,
          "background_image" => $this->resource->background_image,
          "title" => $this->resource->title,
       ];
    }
}
