<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use Sluggable;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'logo',
        'banner',
        'bio',
        'vision',
        'industry_type_id',
        'organization_type_id',
        'team_size_id' ,
        'establishment_date',
        'website',
        'email',
        'phone',
        'country',
        'state',
        'city' ,
        'address',
        'map_link',
    ];
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
        ];
    }
}
