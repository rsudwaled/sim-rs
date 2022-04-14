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
        Schema::create('antrians', function (Blueprint $table) {
            $table->id();
            $table->string('kodebooking')->unique();
            $table->string('nomorkartu')->nullable();
            $table->string('nik', 16);
            $table->string('nohp');
            $table->string('kodepoli');
            $table->string('norm');
            $table->date('tanggalperiksa');
            $table->string('kodedokter');
            $table->string('jampraktek');
            $table->string('jeniskunjungan');
            $table->string('nomorreferensi')->nullable();

            $table->string('jenispasien');
            $table->string('namapoli');
            $table->string('pasienbaru');
            $table->string('namadokter');
            $table->string('nomorantrean');
            $table->string('angkaantrean');

            $table->string('estimasidilayani')->nullable();
            $table->string('sisakuotajkn')->nullable();
            $table->string('kuotajkn')->nullable();
            $table->string('sisakuotanonjkn')->nullable();
            $table->string('kuotanonjkn')->nullable();

            $table->string('taskid')->default(0);
            $table->text('keterangan');
            $table->string('user')->nullable();
            $table->string('status_api')->default(0);
            $table->string('checkin')->nullable();
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
        Schema::dropIfExists('antrians');
    }
};
