<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $guarded = [];

    protected $casts = [
        'install_date' => 'date',
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function usages()
    {
        return $this->hasMany(Usage::class);
    }

    public function services()
    {
        return $this->hasMany(CustomerService::class);
    }

    // Layanan/produk dari data master (satu pelanggan bisa punya banyak)
    public function products()
    {
        return $this->hasMany(CustomerProduct::class);
    }
}