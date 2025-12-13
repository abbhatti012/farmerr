<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;   // ✅ add this

class Admin extends Authenticatable
{
    use HasApiTokens, Notifiable;   // ✅ include here

    protected $guard = 'admin';

    protected $guarded = [];
}
