<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hero_title' => $this->hero_title,
            'hero_subtitle' => $this->hero_subtitle,
            'search_placeholder' => $this->search_placeholder,
            'popular_section_title' => $this->popular_section_title,
            'category_section_title' => $this->category_section_title,
            'announcement_text' => $this->announcement_text,
        ];
    }
}
