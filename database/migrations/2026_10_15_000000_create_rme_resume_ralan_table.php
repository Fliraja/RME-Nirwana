<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rme_resume_ralan', function (Blueprint $table) {
            // Harus sama dengan reg_periksa (latin1) agar FK bisa dibuat.
            $table->engine = 'InnoDB';
            $table->charset = 'latin1';
            $table->collation = 'latin1_swedish_ci';

            $table->string('no_rawat', 17)->primary();
            $table->enum('status', ['otomatis', 'diverifikasi'])->default('otomatis');
            $table->string('tindak_lanjut', 20)->default('pulang');
            $table->date('tgl_kontrol')->nullable();
            $table->string('ket_tindak_lanjut', 100)->nullable();
            $table->string('diverifikasi_oleh', 20)->nullable();
            $table->dateTime('diverifikasi_pada')->nullable();
            $table->dateTime('dibuat_pada');
            $table->char('sidik_sumber', 40);

            $table->foreign('no_rawat', 'fk_rme_resume_ralan_reg')
                ->references('no_rawat')->on('reg_periksa')
                ->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rme_resume_ralan');
    }
};
