<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mining_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('upload_id')->constrained('excel_uploads')->onDelete('cascade');

            // Data Operasional dari Excel
            $table->date('tanggal');
            $table->time('waktu')->nullable();
            $table->string('shift', 50)->nullable();
            $table->string('blok', 100)->nullable();
            $table->string('front', 100)->nullable();
            $table->string('commodity', 100)->nullable();

            // Equipment dari Excel
            $table->string('excavator', 100)->nullable();
            $table->string('dump_truck', 100)->nullable();
            $table->string('dump_loc', 100)->nullable();

            // Produksi
            $table->integer('rit')->nullable();
            $table->decimal('tonnase', 10, 2)->nullable();

            // Additional
            $table->text('keterangan')->nullable();
            $table->timestamps();

            // Indexes untuk performa query
            $table->index(['user_id', 'tanggal']);
            $table->index(['user_id', 'shift']);
            $table->index(['user_id', 'front']);
            $table->index('upload_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mining_data');
    }
};
