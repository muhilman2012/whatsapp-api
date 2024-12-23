<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->string('dokumen_ktp')->nullable(); // Menambahkan kolom dokumen_ktp
            $table->string('dokumen_skuasa')->nullable(); // Menambahkan kolom dokumen_skuasa
        });
    }

    public function down()
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->dropColumn('dokumen_ktp'); // Menghapus kolom saat rollback
            $table->dropColumn('dokumen_skuasa'); // Menghapus kolom saat rollback
        });
    }
};
