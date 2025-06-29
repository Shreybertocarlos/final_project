<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobLocation extends Model
{
    use HasFactory;

    protected $fillable = ['image', 'city_id', 'status'];

    function city() : BelongsTo {
        return $this->belongsTo(City::class);
    }
}
