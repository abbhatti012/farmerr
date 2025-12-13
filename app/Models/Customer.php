<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'email', 'order_id', 'first_name', 'last_name', 'state', 'verified_email',
        'phone', 'tags', 'currency'
    ];

    // Define the relationship to orders using email
    public function orders()
    {
        return $this->hasMany(Order::class, 'email', 'email');
    }
}
