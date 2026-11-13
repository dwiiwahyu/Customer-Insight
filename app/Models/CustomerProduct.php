<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerProduct extends Model
{
    protected $guarded = [];

    protected $casts = [
        'start_date'        => 'date',
        'end_date'          => 'date',
        'sid_complete_date' => 'date',
        'extra'             => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}