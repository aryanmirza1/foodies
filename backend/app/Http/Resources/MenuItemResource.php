<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MenuItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'image_path' => MediaUrl::resolve($this->image_path),
            'is_available' => (bool) $this->is_available,
            'is_popular' => (bool) $this->is_popular,
            'stock_quantity' => $this->stock_quantity,
            'preparation_time' => $this->preparation_time,
            'sort_order' => $this->sort_order,
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
