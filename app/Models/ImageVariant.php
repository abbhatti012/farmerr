<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ImageVariant extends Pivot
{
    protected $table = 'image_variant';
    public $timestamps = false;
}
