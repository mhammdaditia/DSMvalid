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
        Schema::create('validations', function (Blueprint $table) {
            $table->id();
            $table->string('no_unit');
            $table->string('alarm');
            $table->datetime('tanggal');
            $table->string('location');
            $table->string('speed')->nullable();
            $table->string('alarm_id');
            $table->boolean('status');
            $table->text('keterangan');
            $table->text('follow_up')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validations');
    }
};
