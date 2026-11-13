<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->date('invoice_date');                    // tanggal tagihan
            $table->date('paid_date')->nullable();           // null = belum bayar
            $table->unsignedBigInteger('amount');
            $table->integer('delay_days')->default(0);       // hari keterlambatan
            $table->enum('status', ['tepat_waktu', 'terlambat', 'tunggakan']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};