<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::table('appointments',function(Blueprint $table){
   $table->string('change_request_type',20)->nullable(); $table->string('change_requested_by',20)->nullable(); $table->string('change_request_status',20)->nullable();
   $table->dateTime('change_requested_at')->nullable(); $table->dateTime('change_requested_at_target')->nullable(); $table->text('change_request_message')->nullable(); $table->text('change_response')->nullable(); $table->timestamp('reminder_sent_at')->nullable(); $table->timestamp('archived_at')->nullable();
  });
  Schema::table('notifications',fn(Blueprint $table)=>$table->timestamp('archived_at')->nullable()->index());
  Schema::create('priest_verification_logs',function(Blueprint $table){$table->id();$table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();$table->foreignId('priest_id')->constrained('users')->cascadeOnDelete();$table->string('type',20);$table->string('decision',20);$table->text('reason')->nullable();$table->timestamps();$table->index(['priest_id','type','created_at']);});
 }
 public function down(): void { Schema::dropIfExists('priest_verification_logs'); Schema::table('notifications',fn(Blueprint $table)=>$table->dropColumn('archived_at')); Schema::table('appointments',fn(Blueprint $table)=>$table->dropColumn(['change_request_type','change_requested_by','change_request_status','change_requested_at','change_requested_at_target','change_request_message','change_response','reminder_sent_at','archived_at'])); }
};
