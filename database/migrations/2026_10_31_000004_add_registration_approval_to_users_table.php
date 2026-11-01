<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'approval_status')) $table->string('approval_status', 20)->default('approved');
            if (!Schema::hasColumn('users', 'registration_notified_at')) $table->timestamp('registration_notified_at')->nullable();
        });

        DB::table('users')->update(['approval_status'=>'approved']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'registration_notified_at')) $table->dropColumn('registration_notified_at');
            if (Schema::hasColumn('users', 'approval_status')) $table->dropColumn('approval_status');
        });
    }
};
