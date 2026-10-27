<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelanggans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->char('nik', 16)->unique();
            $table->string('email')->unique();
            $table->string('telepon', 20);
            $table->text('alamat');
            $table->string('nomor_sim', 20)->unique();
            $table->string('foto_ktp')->nullable();
            $table->timestamps();

            $table->index('nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggans');
    }
};
