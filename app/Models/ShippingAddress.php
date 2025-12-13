<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingAddress extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id', // foreign key to the orders table
        'type', // type of address, e.g., 'billing' or 'shipping'
        'first_name',
        'last_name',
        'address1',
        'address2',
        'phone',
        'city',
        'zip',
        'province',
        'country',
        'company',
        'latitude',
        'longitude',
        'name',
        'country_code',
        'province_code',
        'created_at',
        'updated_at'
    ];
    
}
