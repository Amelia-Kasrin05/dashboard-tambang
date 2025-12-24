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
        Schema::table('users', function (Blueprint $table) {
            // Hapus kolom role lama jika ada
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }

            // Tambah kolom baru untuk PT Semen Padang
            $table->string('role')->default('user')->after('email'); // admin, supervisor, user
            $table->string('department')->nullable()->after('role'); // Unit/Departemen
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'department']);
        });
    }
};
