<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $guarded = [];

    protected $casts = [
        'invoice_date' => 'date',
        'paid_date'    => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}