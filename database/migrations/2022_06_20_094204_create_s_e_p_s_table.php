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
        Schema::create('s_e_p_s', function (Blueprint $table) {
            $table->id();
            // $table->string('kodebooking')->unique()->index();
            // $table->string('kodekunjungan')->nullable()->unique()->index();
            // $table->string('noSep');
            // $table->string('noRujukan');
            // $table->string('noSurat');
            // // detail sep
            // $table->string('tglSep');
            // $table->string('jnsPelayanan');
            // $table->string('kelasRawat');
            // $table->string('diagnosa');
            // $table->string('poli');
            // $table->string('poliEksekutif');
            // $table->string('catatan');
            // $table->string('kdStatusKecelakaan');
            // $table->string('kdDPJP');
            // // peserta
            // $table->string('noKartu');
            // $table->string('noMr');
            // $table->string('nama');
            // $table->string('noTelp');
            // // kecelakaan
            // $table->string('kdStatusKecelakaan');
            // $table->string('cob');
            // $table->string('katarak');
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
        Schema::dropIfExists('s_e_p_s');
    }
};
