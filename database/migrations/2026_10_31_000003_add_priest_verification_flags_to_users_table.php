<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_priest_verified')) $table->boolean('is_priest_verified')->default(false);
            if (!Schema::hasColumn('users', 'is_confessor_verified')) $table->boolean('is_confessor_verified')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_confessor_verified')) $table->dropColumn('is_confessor_verified');
            if (Schema::hasColumn('users', 'is_priest_verified')) $table->dropColumn('is_priest_verified');
        });
    }
};
