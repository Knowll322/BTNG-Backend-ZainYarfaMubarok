<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penyewaans', function (Blueprint $table) {
            $table->id();
            $table->string('kode_booking', 30)->unique();
            $table->foreignId('mobil_id')->constrained('mobils')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('pelanggan_id')->constrained('pelanggans')->cascadeOnUpdate()->restrictOnDelete();
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->unsignedSmallInteger('durasi_hari');
            $table->unsignedBigInteger('harga_sewa_per_hari');
            $table->unsignedBigInteger('total_biaya');
            $table->unsignedBigInteger('denda')->default(0);
            $table->enum('status', ['booked', 'berjalan', 'selesai', 'batal'])->default('booked')->index();
            $table->date('tanggal_dikembalikan')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['mobil_id', 'tanggal_mulai', 'tanggal_selesai']);
            $table->index(['pelanggan_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penyewaans');
    }
};
