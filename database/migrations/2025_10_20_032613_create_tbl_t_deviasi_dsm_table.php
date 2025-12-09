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
        Schema::create('tbl_t_deviasi_dsm', function (Blueprint $table) {
            $table->id();
            $table->string('alarm_id', 50);
            $table->string('no_unit', 50);
            $table->string('alarm', 100);
            $table->datetime('tanggal');
            $table->string('location', 255)->nullable();
            $table->float('speed')->nullable();
            
            // Kolom untuk video
            $table->boolean('IsVideoSent')->default(false);
            $table->string('VideoFileName', 255)->nullable();
            
            // HAPUS timestamps karena tidak diperlukan
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_t_deviasi_dsm');
    }
};
