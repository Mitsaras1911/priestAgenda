<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('users', 'approval_status')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('approval_status', 20)->default('approved')->change());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'approval_status')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('approval_status', 20)->default('pending')->change());
        }
    }
};
