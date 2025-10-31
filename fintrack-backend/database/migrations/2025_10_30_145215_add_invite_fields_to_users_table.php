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
            $table->string('username')->unique()->nullable()->after('email');
            $table->foreignId('invited_by')->nullable()->constrained('users')->onDelete('set null')->after('role');
            $table->timestamp('invited_at')->nullable()->after('invited_by');
            $table->enum('status', ['invited', 'active'])->default('active')->after('invited_at');
            $table->timestamp('password_changed_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['invited_by']);
            $table->dropColumn(['username', 'invited_by', 'invited_at', 'status', 'password_changed_at']);
        });
    }
};
