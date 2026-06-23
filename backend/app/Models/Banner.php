<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'image_path',
        'button_text',
        'button_link',
        'is_active',
        'sort_order',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'end_date' => 'datetime',
            'is_active' => 'boolean',
            'start_date' => 'datetime',
        ];
    }
}
