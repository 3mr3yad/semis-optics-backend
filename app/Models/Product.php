<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'badge',
        'price',
        'currency',
        'rating_score',
        'total_reviews',
        'main_image',
        'gallery',
        'magnification',
        'frame_colors',
        'features',
        'technical_specifications',
        'trust_badges',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'rating_score' => 'decimal:1',
            'gallery' => 'array',
            'magnification' => 'array',
            'frame_colors' => 'array',
            'features' => 'array',
            'technical_specifications' => 'array',
            'trust_badges' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
