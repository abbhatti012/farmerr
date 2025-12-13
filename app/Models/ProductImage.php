<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'product_id',
        'src',
        'alt',
        'position',
        'width',
        'height',
        'admin_graphql_api_id',
        'is_main',
        'shopify_created_at',
        'shopify_updated_at',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variants()
    {
        return $this->belongsToMany(ProductVariant::class, 'image_variant', 'product_image_id', 'product_variant_id');
    }
}
