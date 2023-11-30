<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categorie extends Model
{
    protected $guarded = ['id'];
    protected $timestamps = false;
    use HasFactory;
}
