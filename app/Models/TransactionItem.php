<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    protected $fillable = [
        'transaction_id', 'menu_id', 'menu_name_snapshot',
        'price_snapshot', 'qty', 'note', 'line_total',
    ];
}
