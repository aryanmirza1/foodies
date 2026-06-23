<?php

namespace App\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RestaurantSettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'restaurant_name' => $this->restaurant_name,
            'app_name' => $this->app_name,
            'tagline' => $this->tagline,
            'logo_path' => MediaUrl::resolve($this->logo_path),
            'app_icon_path' => MediaUrl::resolve($this->app_icon_path),
            'phone' => $this->phone,
            'email' => $this->email,
            'whatsapp' => $this->whatsapp,
            'address' => $this->address,
            'about_text' => $this->about_text,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'background_color' => $this->background_color,
            'button_color' => $this->button_color,
            'is_open' => (bool) $this->is_open,
        ];
    }
}
