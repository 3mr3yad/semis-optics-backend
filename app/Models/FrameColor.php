<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrameColor extends Model
{
    protected $table = 'frame_colors';

    protected $fillable = [
        'code',
        'name',
        'hex',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_frame_color')
            ->withPivot(['available'])
            ->withTimestamps();
    }
}
