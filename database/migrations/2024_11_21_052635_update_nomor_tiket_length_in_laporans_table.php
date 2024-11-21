<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->string('nomor_tiket', 7)->change(); // Ubah panjang kolom menjadi 7 karakter
        });
    }

    public function down()
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->string('nomor_tiket', 5)->change(); // Kembalikan ke panjang 5 karakter jika rollback
        });
    }
};
