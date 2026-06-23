<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeSection extends Model
{
    protected $fillable = [
        'hero_title',
        'hero_subtitle',
        'search_placeholder',
        'popular_section_title',
        'category_section_title',
        'announcement_text',
    ];
}
