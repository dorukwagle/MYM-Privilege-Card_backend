<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class User extends Model
{
    use HasSpatial;

    protected $guarded = ['id'];
    protected $fillable = ['coordinates'];

    protected $casts = [
        'coordinates' => Point::class
    ];

    use HasFactory;
}
