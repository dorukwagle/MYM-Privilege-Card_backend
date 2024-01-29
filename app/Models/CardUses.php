<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CardUses extends Model
{
    protected $table = 'cards_uses';
    protected $guarded = ['id'];
    use HasFactory;
}
