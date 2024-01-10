<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    protected $primaryKey = 'prim';
    protected $guarded = ['prim'];
    use HasFactory;
}
