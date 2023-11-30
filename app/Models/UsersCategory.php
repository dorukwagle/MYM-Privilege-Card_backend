<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersCategory extends Model
{
    protected $table = 'users_categories';
    public $timestamps = false;
    protected $guarded = ['id'];
    use HasFactory;
}
