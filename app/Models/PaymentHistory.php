<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    protected $table = 'payment_histories';
    protected $guarded = ['id'];
    public $timestamps = false;
    use HasFactory;
}
