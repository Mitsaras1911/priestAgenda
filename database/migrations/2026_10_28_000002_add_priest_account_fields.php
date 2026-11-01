<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) $table->string('phone')->nullable()->unique();
            if (!Schema::hasColumn('users', 'role')) $table->string('role')->default('visitor');
            if (!Schema::hasColumn('users', 'slug')) $table->string('slug')->nullable()->unique();
            if (!Schema::hasColumn('users', 'parish')) $table->string('parish')->nullable();
            if (!Schema::hasColumn('users', 'city')) $table->string('city')->nullable();
            if (!Schema::hasColumn('users', 'bio')) $table->text('bio')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['phone','role','slug','parish','city','bio'] as $column) if (Schema::hasColumn('users', $column)) $table->dropColumn($column);
        });
    }
};
