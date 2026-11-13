<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->unique();      // contoh: 20462 (ditampilkan sebagai #20462)
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('internet_number')->unique();
            $table->string('product');                       // contoh: Indibiz 50 Mbps
            $table->string('industry')->nullable();          // dipakai untuk rekomendasi (F&B, Retail, dll.)
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
            $table->string('wilayah');
            $table->date('install_date');
            $table->text('address')->nullable();
            $table->unsignedBigInteger('monthly_revenue')->default(0);
            $table->unsignedTinyInteger('health_score')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};