<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestaurantSetting extends Model
{
    protected $fillable = [
        'restaurant_name',
        'app_name',
        'tagline',
        'logo_path',
        'app_icon_path',
        'phone',
        'email',
        'whatsapp',
        'address',
        'about_text',
        'primary_color',
        'secondary_color',
        'background_color',
        'button_color',
        'is_open',
    ];

    protected function casts(): array
    {
        return [
            'is_open' => 'boolean',
        ];
    }
}
