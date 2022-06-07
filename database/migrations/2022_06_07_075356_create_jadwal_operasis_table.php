<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jadwal_operasis', function (Blueprint $table) {
            $table->id();
            $table->string('kodebooking')->unique();
            $table->date('tanggaloperasi');
            $table->string('kodetindakan');
            $table->string('jenistindakan');
            $table->string('kodepoli');
            $table->string('namapoli');
            $table->string('terlaksana');
            $table->string('nopeserta');
            $table->string('namapeserta');
            $table->string('lastupdate');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jadwal_operasis');
    }
};
