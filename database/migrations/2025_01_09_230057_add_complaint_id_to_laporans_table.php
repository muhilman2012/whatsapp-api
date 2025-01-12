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
            $table->unsignedBigInteger('complaint_id')->nullable(); // Menambahkan kolom complaint_id  
        });  
    }  
    
    public function down()  
    {  
        Schema::table('laporans', function (Blueprint $table) {  
            $table->dropColumn('complaint_id'); // Menghapus kolom complaint_id  
        });  
    }
};
