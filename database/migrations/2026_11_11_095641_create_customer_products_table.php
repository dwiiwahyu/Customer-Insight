<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('account_num')->nullable()->index();
            $table->string('product_group')->nullable();
            $table->string('product_name')->nullable();
            $table->string('circuit_speed')->nullable();
            $table->string('contract_no')->nullable()->index();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('sid_complete_date')->nullable();
            $table->unsignedBigInteger('abonemen')->nullable();
            $table->unsignedBigInteger('revenue')->default(0);
            $table->json('extra')->nullable();                   // GROUP1-5 & SEGMEN_BILL_OLD
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_products');
    }
};