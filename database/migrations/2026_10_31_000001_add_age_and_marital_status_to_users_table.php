<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'age')) $table->unsignedTinyInteger('age')->nullable();
            if (!Schema::hasColumn('users', 'is_married')) $table->boolean('is_married')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'age')) $table->dropColumn('age');
            if (Schema::hasColumn('users', 'is_married')) $table->dropColumn('is_married');
        });
    }
};
