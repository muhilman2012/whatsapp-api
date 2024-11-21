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
        Schema::create('laporans', function (Blueprint $table) {
            $table->id(); // ID otomatis
            $table->string('nomor_tiket', 7)->unique(); // Nomor tiket 7 digit angka unik
            $table->string('nomor_pengadu')->nullable(); // Nomor telepon pengadu
            $table->string('email')->nullable(); // Email pengadu
            $table->string('nama_lengkap'); // Nama lengkap pengadu
            $table->string('nik', 16); // NIK (16 digit angka)
            $table->enum('jenis_kelamin', ['L', 'P']); // Jenis kelamin (L/P)
            $table->text('alamat_lengkap'); // Alamat lengkap pengadu
            $table->string('judul'); // Judul laporan
            $table->text('detail'); // Detail laporan
            $table->string('lokasi')->nullable(); // Lokasi (opsional)
            $table->string('dokumen_pendukung')->nullable(); // Dokumen pendukung (opsional)
            $table->date('tanggal_kejadian')->nullable(); // Tanggal kejadian dalam format YYYY-MM-DD
            $table->string('status')->default('Diproses'); // Status laporan default: Diproses
            $table->text('tanggapan')->nullable(); // Tanggapan dari petugas
            $table->string('klasifikasi')->nullable(); // Klasifikasi laporan (opsional)
            $table->string('kategori')->nullable(); // Kategori laporan (opsional)
            $table->string('disposisi')->nullable(); // Disposisi laporan (opsional)
            $table->timestamps(); // Kolom created_at dan updated_at
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
