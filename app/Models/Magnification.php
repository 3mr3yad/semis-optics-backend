<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Magnification extends Model
{
    protected $fillable = [
        'code',
        'label',
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
        return $this->belongsToMany(Product::class, 'product_magnification')
            ->withPivot(['available'])
            ->withTimestamps();
    }
}
