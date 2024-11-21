<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToNikInLaporansTable extends Migration
{
    public function up()
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->index('nik'); // Tambahkan index pada kolom NIK
        });
    }

    public function down()
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->dropIndex(['nik']); // Hapus index jika rollback
        });
    }
}