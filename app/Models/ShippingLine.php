<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingLine extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id', 'title', 'price', 'discounted_price'
    ];
}
