<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobils', function (Blueprint $table) {
            $table->id();
            $table->string('plat_nomor', 15)->unique();
            $table->string('nama');
            $table->string('brand', 50)->index();
            $table->unsignedBigInteger('harga');
            $table->unsignedBigInteger('harga_sewa_harian');
            $table->unsignedSmallInteger('tahun');
            $table->enum('transmisi', ['Manual', 'Matic', 'CVT']);
            $table->enum('bahan_bakar', ['Bensin', 'Diesel', 'Hybrid', 'Listrik']);
            $table->unsignedTinyInteger('kapasitas_kursi');
            $table->enum('status', ['tersedia', 'disewa', 'servis', 'sold'])->default('tersedia')->index();
            $table->string('foto')->nullable();
            $table->text('deskripsi')->nullable();
            $table->timestamps();

            $table->index(['status', 'brand']);
            $table->index('harga_sewa_harian');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobils');
    }
};
