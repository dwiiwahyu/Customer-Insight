<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('service');                       // Internet / Telepon / TV
            $table->string('package');                       // 100 Mbps / Unlimited
            $table->unsignedInteger('used_value')->default(0);
            $table->unsignedInteger('limit_value')->nullable(); // null = tanpa batas
            $table->string('unit');                          // GB / menit / jam
            $table->enum('status', ['normal', 'waspada', 'limit'])->default('normal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_services');
    }
};