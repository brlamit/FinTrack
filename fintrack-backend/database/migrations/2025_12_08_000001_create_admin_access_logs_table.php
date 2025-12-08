<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('admin_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_user_id');
            $table->string('action');
            $table->string('reason');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['admin_user_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_access_logs');
    }
};
