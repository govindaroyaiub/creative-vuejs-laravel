<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    protected $fillable = ['name', 'client', 'total_amount'];

    public function subBills(): HasMany
    {
        return $this->hasMany(SubBill::class);
    }
}
