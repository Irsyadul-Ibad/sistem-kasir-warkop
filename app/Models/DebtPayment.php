<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebtPayment extends Model
{
    use SoftDeletes;

    protected $fillable = ['transaction_id', 'user_id', 'amount', 'payment_method', 'paid_at'];

    protected $casts = ['paid_at' => 'datetime'];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
