<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoteAttributes extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id', // foreign key to the orders table
        'name', // type of address, e.g., 'billing' or 'shipping'
        'value',
        'created_at',
        'updated_at'
    ];
}
