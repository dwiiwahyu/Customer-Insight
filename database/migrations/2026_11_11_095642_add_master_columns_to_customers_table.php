<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nomor internet/akun itu milik layanan, jadi tidak boleh unik per pelanggan
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['internet_number']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('internet_number')->nullable()->change();
            $table->string('subsegment')->nullable()->after('industry');
            $table->string('sto')->nullable()->after('wilayah');
            $table->string('am_name')->nullable()->after('sto');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['subsegment', 'sto', 'am_name']);
        });
    }
};