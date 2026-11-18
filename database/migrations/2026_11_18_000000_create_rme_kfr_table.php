<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rme_kfr', function (Blueprint $table) {
            // Harus sama dengan reg_periksa (latin1) agar FK bisa dibuat.
            $table->engine = 'InnoDB';
            $table->charset = 'latin1';
            $table->collation = 'latin1_swedish_ci';

            $table->string('no_rawat', 17)->primary();
            $table->string('penunjang', 500)->default('');
            $table->string('target', 300)->default('');
            $table->string('edukasi', 300)->default('');
            $table->string('disimpan_oleh', 20);
            $table->dateTime('disimpan_pada');

            $table->foreign('no_rawat', 'fk_rme_kfr_reg')
                ->references('no_rawat')->on('reg_periksa')
                ->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rme_kfr');
    }
};
