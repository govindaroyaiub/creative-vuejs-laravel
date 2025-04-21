<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubBill extends Model
{
    protected $fillable = ['bill_id', 'item', 'quantity', 'unit_price', 'amount'];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }
}
