<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rme_asesmen_igd', function (Blueprint $table) {
            // Harus sama dengan reg_periksa (latin1) agar FK bisa dibuat.
            $table->engine = 'InnoDB';
            $table->charset = 'latin1';
            $table->collation = 'latin1_swedish_ci';

            $table->string('no_rawat', 17)->primary();
            $table->string('dibuat_oleh', 20);
            $table->dateTime('dibuat_pada');
            $table->string('diubah_oleh', 20);
            $table->dateTime('diubah_pada');

            $table->foreign('no_rawat', 'fk_rme_asesmen_igd_reg')
                ->references('no_rawat')->on('reg_periksa')
                ->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rme_asesmen_igd');
    }
};
