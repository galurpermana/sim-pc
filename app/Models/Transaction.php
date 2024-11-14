<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'total', 'payment_method', 'payment_proof'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactionProducts()
    {
        return $this->hasMany(TransactionProducts::class);
    }
}
