<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'discount_price',
        'image_path',
        'is_available',
        'is_popular',
        'stock_quantity',
        'preparation_time',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'discount_price' => 'decimal:2',
            'is_available' => 'boolean',
            'is_popular' => 'boolean',
            'price' => 'decimal:2',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
