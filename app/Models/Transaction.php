<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_uuid', 'invoice_no', 'user_id', 'customer_id', 'table_label',
        'subtotal', 'discount', 'total', 'payment_method', 'amount_paid',
        'change_amount', 'status', 'void_reason', 'paid_at',
    ];

    protected $casts = ['paid_at' => 'datetime'];

    public function items() { return $this->hasMany(TransactionItem::class); }
    public function user()  { return $this->belongsTo(User::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function debtPayments() { return $this->hasMany(DebtPayment::class); }
}
