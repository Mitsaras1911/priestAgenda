<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id(); $table->foreignId('priest_id')->constrained('users')->cascadeOnDelete();
            $table->string('service_type'); $table->unsignedTinyInteger('weekday');
            $table->time('starts_at'); $table->time('ends_at'); $table->unsignedSmallInteger('slot_minutes')->default(30); $table->timestamps();
        });
        Schema::create('blocked_dates', function (Blueprint $table) {
            $table->id(); $table->foreignId('priest_id')->constrained('users')->cascadeOnDelete();
            $table->date('date'); $table->string('service_type')->default('all'); $table->string('reason')->nullable(); $table->timestamps();
            $table->index(['priest_id', 'date']);
        });
        Schema::create('appointments', function (Blueprint $table) {
            $table->id(); $table->foreignId('priest_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->string('service_type'); $table->dateTime('starts_at'); $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->string('status')->default('pending'); $table->text('message')->nullable(); $table->dateTime('suggested_at')->nullable();
            $table->text('priest_response')->nullable(); $table->timestamps(); $table->index(['priest_id', 'starts_at', 'status']);
        });
        Schema::create('login_codes', function (Blueprint $table) {
            $table->id(); $table->string('destination')->index(); $table->string('code_hash'); $table->timestamp('expires_at'); $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_codes'); Schema::dropIfExists('appointments'); Schema::dropIfExists('blocked_dates'); Schema::dropIfExists('availabilities');
    }
};
