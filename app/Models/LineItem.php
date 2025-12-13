<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LineItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id', 'product_id','line_items_id' ,'variant_id', 'quantity', 'price',
        'total_discount', 'name', 'sku', 'fulfillment_status',
        'requires_shipping', 'taxable', 'title'
    ];
}
