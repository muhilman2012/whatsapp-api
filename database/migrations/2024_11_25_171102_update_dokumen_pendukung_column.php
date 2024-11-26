<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('laporans', function (Blueprint $table) {
            // Ubah tipe kolom dokumen_pendukung menjadi text
            $table->text('dokumen_pendukung')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('laporans', function (Blueprint $table) {
            // Kembalikan tipe kolom menjadi varchar(255) jika perlu rollback
            $table->string('dokumen_pendukung', 255)->nullable()->change();
        });
    }
};