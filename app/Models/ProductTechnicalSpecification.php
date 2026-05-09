<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTechnicalSpecification extends Model
{
    protected $table = 'product_technical_specifications';

    protected $fillable = [
        'product_id',
        'parameter',
        'specification',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
