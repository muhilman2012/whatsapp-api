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
            // Ubah default pada kolom status
            $table->string('status')->default('Proses verifikasi dan telaah')->change();
        });
    
        // Perbarui semua data lama dengan status "Diproses" menjadi status baru
        \DB::table('laporans')->where('status', 'Diproses')->update(['status' => 'Proses verifikasi dan telaah']);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('laporans', function (Blueprint $table) {
            // Balikkan default status ke "Diproses"
            $table->string('status')->default('Diproses')->change();
        });
    
        // Balikkan status ke "Diproses" jika rollback
        \DB::table('laporans')->where('status', 'Proses verifikasi dan telaah')->update(['status' => 'Diproses']);
    }
};
