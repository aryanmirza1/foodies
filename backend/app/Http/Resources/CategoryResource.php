<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'image_path' => MediaUrl::resolve($this->image_path),
            'sort_order' => $this->sort_order,
            'menu_items_count' => $this->whenCounted('menuItems'),
        ];
    }
}
