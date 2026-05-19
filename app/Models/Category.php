<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;

class Category extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
    ];
}