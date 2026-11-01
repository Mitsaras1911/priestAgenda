<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::table('users')->where('role', 'visitor')->update(['approval_status' => 'approved']);
        DB::table('notifications')->where('type', 'App\\Notifications\\RegisteredUserPendingApproval')->delete();

        if (Schema::hasColumn('users', 'registration_notified_at')) {
            Schema::table('users', fn ($table) => $table->dropColumn('registration_notified_at'));
        }
    }

    public function down(): void
    {
        // Visitor approvals are intentionally not rolled back; visitors no longer require review.
    }
};
