<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'product_id',
        'title',
        'price',
        'compare_at_price',
        'position',
        'sku',
        'taxable',
        'fulfillment_service',
        'inventory_management',
        'inventory_policy',
        'requires_shipping',
        'grams',
        'weight',
        'weight_unit',
        'inventory_item_id',
        'inventory_quantity',
        'old_inventory_quantity',
        'option1',
        'option2',
        'option3',
        'admin_graphql_api_id',
        'shopify_created_at',
        'shopify_updated_at',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // image_variant pivot
    public function images()
    {
        return $this->belongsToMany(ProductImage::class, 'image_variant', 'product_variant_id', 'product_image_id');
    }
}
