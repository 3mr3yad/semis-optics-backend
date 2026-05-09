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
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'rating_score' => 'decimal:1',
            'is_active' => 'boolean',
        ];
    }

    public function media()
    {
        return $this->hasMany(ProductMedia::class)->orderBy('sort_order');
    }

    public function features()
    {
        return $this->hasMany(ProductFeature::class)->orderBy('sort_order');
    }

    public function technicalSpecifications()
    {
        return $this->hasMany(ProductTechnicalSpecification::class)->orderBy('sort_order');
    }

    public function magnifications()
    {
        return $this->belongsToMany(Magnification::class, 'product_magnification')
            ->withPivot(['available'])
            ->withTimestamps();
    }

    public function frameColors()
    {
        return $this->belongsToMany(FrameColor::class, 'product_frame_color')
            ->withPivot(['available'])
            ->withTimestamps();
    }

    public function trustBadges()
    {
        return $this->belongsToMany(TrustBadge::class, 'product_trust_badge')
            ->withTimestamps();
    }
}
