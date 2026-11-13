<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usage extends Model
{
    protected $guarded = [];

    protected $casts = [
        'period'       => 'date',
        'online_hours' => 'float',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}