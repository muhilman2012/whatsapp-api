<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('laporans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_tiket', 5)->unique();
            $table->string('namalengkap');
            $table->string('nik', 16);
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->text('alamatlengkap');
            $table->enum('jenis_laporan', ['Pengaduan', 'Aspirasi', 'Permintaan Informasi']);
            $table->string('judul');
            $table->text('detail');
            $table->string('lokasi')->nullable();
            $table->string('dokumenpendukung')->nullable();
            $table->date('tanggalkejadian')->nullable(); // Khusus untuk pengaduan
            $table->string('status')->default('Diproses');
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laporans');
    }
};
